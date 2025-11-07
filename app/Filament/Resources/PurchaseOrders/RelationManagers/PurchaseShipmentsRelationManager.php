<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Resources\PurchaseShipments\Tables\PurchaseShipmentTable;
use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use App\Traits\Filament\HasShipmentFormFields;
use App\Traits\Filament\HasShipmentLineValidation;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Core\PurchaseShipmentService;
use Closure;

use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;

class PurchaseShipmentsRelationManager extends RelationManager
{
    use HasShipmentFormFields, HasShipmentLineValidation;

    protected static string $relationship = 'purchaseShipments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Shipments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Tabs::make(__('Shipment'))
                ->tabs([
                    S\Tabs\Tab::make(__('Shipment Info'))
                        ->schema([
                            ...$this->shipmentInfoFields()
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

                    S\Tabs\Tab::make(__('Products'))
                        ->schema([
                            $this->shipmentLines()
                        ])
                        ->columns(),

                    S\Tabs\Tab::make(__('Costs & Notes'))
                        ->schema([
                            ...$this->costsAndNotesFields()
                        ]),
                ])
                ->columnSpanFull(),

        ])
            ->columns(2);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return
            $table->columns([
                ...PurchaseShipmentTable::configure($table)->getColumns(),
            ])
            ->modelLabel(__('Shipment'))
            ->pluralModelLabel(__('Shipments'))
            ->headerActions([
                A\CreateAction::make()
                    ->after(function (PurchaseShipment $record): void {
                        app(PurchaseShipmentService::class)->syncShipmentInfo($record->id);
                        $this->dispatch('refresh-order-status');
                    })
                    ->disabled(fn(): bool => $this->getOwnerRecord()->order_number == null)
                    ->modal()->slideOver(),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(function (PurchaseShipment $record): void {
                        app(PurchaseShipmentService::class)->syncShipmentInfo($record->id);
                        $this->dispatch('refresh-order-status');
                    })
                    ->modal()->slideOver(),

                A\DeleteAction::make(),
            ]);
    }

    public function shipmentInfoFields(): array
    {
        return [
            S\Flex::make([
                F\ToggleButtons::make('shipment_status')
                    ->label(__('Shipment Status'))
                    ->options(\App\Enums\ShipmentStatusEnum::class)
                    ->default(\App\Enums\ShipmentStatusEnum::Pending->value)
                    ->grouped()
                    ->grow(false)
                    ->disableOptionWhen(fn(string $value, string $operation): bool
                    => $operation === 'create'
                        && $value === \App\Enums\ShipmentStatusEnum::Cancelled->value)
                    ->columnSpanFull()
                    ->required(),

                F\TextInput::make('tracking_no')
                    ->label(__('Tracking Number')),
            ])
                ->from('lg')
                ->columnSpanFull(),

            S\Group::make([
                F\Select::make('staff_docs_id')
                    ->label(__('Docs Staff'))
                    ->relationship(
                        name: 'staffDocs',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->staff_docs_id)
                    ->required(),
                F\Select::make('staff_declarant_id')
                    ->relationship(
                        name: 'staffDeclarant',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->staff_declarant_id)
                    ->required(),
                F\Select::make('staff_declarant_processing_id')
                    ->relationship(
                        name: 'staffDeclarantProcessing',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->staff_declarant_processing_id)
                    ->required(),
            ])
                ->columns([
                    'default' => 2,
                    'lg' => 3,
                ])
                ->columnSpanFull(),

            F\Select::make('port_id')
                ->label(__('Port'))
                ->relationship(
                    name: 'port',
                    titleAttribute: 'port_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->default(fn($livewire) => $livewire->getOwnerRecord()?->import_port_id)
                ->required(function ($livewire): bool {
                    /** @var \App\Models\PurchaseOrder $order */
                    $order = $livewire->getOwnerRecord();
                    return !$order?->is_skip_invoice && $order?->is_foreign;
                }),

            F\Select::make('warehouse_id')
                ->label(__('Warehouse'))
                ->relationship(
                    name: 'warehouse',
                    titleAttribute: 'warehouse_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->default(fn($livewire) => $livewire->getOwnerRecord()?->import_warehouse_id)
                ->required(),

            // Sử dụng helper function cho ETD/ETA fields
            ...static::etdEtaFields(),
        ];
    }

    public function shipmentLines(): F\Repeater
    {
        return F\Repeater::make('purchaseShipmentLines')
            ->label(__('Products'))
            ->relationship('purchaseShipmentLines')
            ->hiddenLabel()
            ->table(static::shipmentLinesTableHeaders())
            ->schema(function (?PurchaseShipment $record): array {
                $shipment = $record;
                return [
                    F\Select::make('product_id')
                        ->label(__('Product'))
                        ->relationship(
                            name: 'product',
                            titleAttribute: 'product_full_name',
                            modifyQueryUsing: fn(Builder $query): Builder
                            => $this->filterProductsForShipment($query)
                        )
                        ->afterStateUpdated(function (?string $state, F\Select $component, Set $set) use ($shipment) {
                            $this->handleProductSelectionUpdate($state, $component, $set, $shipment);
                        })
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required(),

                    __number_field('qty')
                        ->rules([
                            fn(Get $get, F\TextInput $component): Closure
                            => $this->createQuantityValidationRule($get, $component, $shipment),
                        ])
                        ->required(),

                    __number_field('unit_price')
                        ->label(__('Unit Price'))
                        ->suffix(fn() => $this->getOwnerRecord()?->currency ?? 'N/A')
                        ->required(),

                    __number_field('contract_price')
                        ->label(__('Contract Price'))
                        ->suffix(fn() => $this->getOwnerRecord()?->currency ?? 'N/A'),

                ];
            })
            ->minItems(1)
            ->columnSpanFull()
            ->addActionLabel(__('Add Product'))
            ->required()
        ;
    }
}
