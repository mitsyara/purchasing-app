<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Resources\PurchaseShipments\Tables\PurchaseShipmentTable;
use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use App\Services\PurchaseShipment\CallAllPurchaseShipmentServices;

use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Livewire\Component as Livewire;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;

class PurchaseShipmentsRelationManager extends RelationManager
{
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
                        ->schema([...static::shipmentInfoFields()])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

                    S\Tabs\Tab::make(__('Products'))
                        ->schema([static::shipmentLines()])
                        ->columns(),

                    S\Tabs\Tab::make(__('Costs & Notes'))
                        ->schema([...static::costsAndNotesFields()]),
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
                        new CallAllPurchaseShipmentServices($record);
                        $this->dispatch('refresh-order-status');
                    })
                    ->disabled(fn(): bool => $this->getOwnerRecord()->order_number == null)
                    ->modal()->slideOver(),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(function (PurchaseShipment $record): void {
                        new CallAllPurchaseShipmentServices($record);
                        $this->dispatch('refresh-order-status');
                    })
                    ->modal()->slideOver(),

                A\DeleteAction::make(),
            ]);
    }

    public static function shipmentInfoFields(): array
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
                ->default(fn($livewire) => $livewire->getOwnerRecord()?->port_id)
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

            \Filament\Schemas\Components\Fieldset::make(__('ETD'))
                ->schema([
                    F\DatePicker::make('etd_min')->label(__('From'))
                        ->default(fn(RelationManager $livewire) => $livewire->getOwnerRecord()?->etd_min)
                        ->requiredWithoutAll(['etd_max', 'eta_min', 'eta_max', 'atd', 'ata'])
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->minDate(fn($livewire)
                        => $livewire->getOwnerRecord()->order_date ?? today()),

                    F\DatePicker::make('etd_max')->label(__('To'))
                        ->default(fn($livewire) => $livewire->getOwnerRecord()?->etd_max)
                        ->requiredWithoutAll(['etd_min', 'eta_min', 'eta_max', 'atd', 'ata'])
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->minDate(fn(callable $get, RelationManager $livewire)
                        => $get('etd_min') ?? $livewire->getOwnerRecord()->order_date ?? today()),
                ])
                ->columns([
                    'default' => 2,
                    'lg' => 1,
                    'xl' => 2,
                ]),

            \Filament\Schemas\Components\Fieldset::make(__('ETA'))
                ->schema([
                    F\DatePicker::make('eta_min')->label(__('From'))
                        ->default(fn($livewire) => $livewire->getOwnerRecord()?->eta_min)
                        ->requiredWithoutAll(['etd_min', 'etd_max', 'eta_max', 'atd', 'ata'])
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->minDate(fn(callable $get, RelationManager $livewire)
                        => $get('etd_max') ?? $livewire->getOwnerRecord()->order_date ?? today()),

                    F\DatePicker::make('eta_max')->label(__('To'))
                        ->default(fn($livewire) => $livewire->getOwnerRecord()?->eta_max)
                        ->requiredWithoutAll(['etd_min', 'etd_max', 'eta_min', 'atd', 'ata'])
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->minDate(fn(callable $get, RelationManager $livewire)
                        => $get('eta_min') ?? $livewire->getOwnerRecord()->order_date ?? today()),
                ])
                ->columns([
                    'default' => 2,
                    'lg' => 1,
                    'xl' => 2,
                ]),
        ];
    }


    public static function shipmentLines(): F\Repeater
    {
        return F\Repeater::make('purchaseShipmentLines')
            ->label(__('Products'))
            ->relationship('purchaseShipmentLines')
            ->hiddenLabel()
            ->table(static::shipmentLinesRepeaterHeaders())
            ->schema([
                F\Select::make('product_id')
                    ->label(__('Product'))
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'product_full_name',
                        modifyQueryUsing: fn(Builder $query, Livewire $livewire): Builder
                        => $query->whereIn(
                            'id',
                            $livewire->getOwnerRecord()
                                ?->purchaseOrderLines()
                                ->pluck('product_id') ?? []
                        ),
                    )
                    ->afterStateUpdated(fn($state, Set $set, Livewire $livewire, ?PurchaseShipmentLine $record)
                    => static::recommendShipmentLineQty($state, $set, $livewire, $record))
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->required(),

                __number_field('qty')
                    ->rules([
                        fn(Get $get, ?PurchaseShipmentLine $record, Livewire $livewire): \Closure =>
                        fn(string $attribute, mixed $value, \Closure $fail) =>
                        static::validateQty($get, $livewire, $record, $value, $fail),
                    ])
                    ->required(),

            ])
            ->minItems(1)
            ->columnSpanFull()
            ->addActionLabel(__('Add Product'))
            ->required()
        ;
    }
    // Repeater Table Headers
    public static function shipmentLinesRepeaterHeaders(): array
    {
        return [
            F\Repeater\TableColumn::make('Product')
                ->markAsRequired(),
            F\Repeater\TableColumn::make('Qty')
                ->markAsRequired()
                ->width('180px'),
        ];
    }

    public static function costsAndNotesFields(): array
    {
        return [
            S\Fieldset::make(__('Extra Costs'))
                ->schema([
                    F\Repeater::make('extra_costs')
                        ->hiddenLabel()
                        ->simple(
                            __number_field('extra_cost')
                                ->suffix('VND')
                                ->required(),
                        )
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->grid(3)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            __notes()
                ->rows(5),
        ];
    }

    // Helper methods
    public static function recommendShipmentLineQty(
        ?string $state,
        Set $set,
        Livewire $livewire,
        ?PurchaseShipmentLine $record
    ): void {
        $productId = (int) $state;
        $order = $livewire->getOwnerRecord();

        if ($productId && $order) {
            // Tính tổng qty đã giao từ bảng purchase_shipment_lines,
            // cho tất cả shipment thuộc order này, loại trừ record hiện tại (nếu có).
            $shippedQty = PurchaseShipmentLine::whereHas('purchaseShipment', function (Builder $q) use ($order) {
                $q->where('purchase_order_id', $order->id);
            })
                ->where('product_id', $productId)
                ->when($record?->id, fn($q, $rid) => $q->where('id', '!=', $rid))
                ->sum('qty');

            $orderedQty = $order->purchaseOrderLines()
                ->where('product_id', $productId)
                ->first()
                ?->qty ?? 0;

            $recommendedQty = max($orderedQty - $shippedQty, 0);
            $recommendedQty = __number_string_converter($recommendedQty);

            if ($recommendedQty > 0) {
                $set('qty', $recommendedQty);
            } else {
                $set('qty', null);
            }
        } else {
            $set('qty', null);
        }
    }


    public static function validateQty(
        Get $get,
        Livewire $livewire,
        ?PurchaseShipmentLine $record,
        $value,
        \Closure $fail
    ): void {
        if (!($livewire instanceof static)) {
            throw new \Exception('Component is not an instance of the expected RelationManager.');
        }

        $order = $livewire->getOwnerRecord();

        // Lấy qty trong order line cho product hiện tại
        $orderLineQty = \App\Models\PurchaseOrderLine::where('purchase_order_id', $order->id)
            ->where('product_id', $get('product_id'))
            ->first()?->qty ?? 0;

        // Tổng qty đã giao (trên tất cả purchase_shipment_lines thuộc order), loại trừ current record
        $shippedQty = PurchaseShipmentLine::whereHas('purchaseShipment', function (Builder $q) use ($order) {
            $q->where('purchase_order_id', $order->id);
        })
            ->where('product_id', $get('product_id'))
            ->when($record?->id, fn($q, $rid) => $q->where('id', '!=', $rid))
            ->sum('qty');

        if ($value + $shippedQty > $orderLineQty) {
            $fail(__('Remaining: :qty', ['qty' => $orderLineQty - $shippedQty]));
        }
    }
}
