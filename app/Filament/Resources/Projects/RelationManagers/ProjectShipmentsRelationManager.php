<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectShipment;
use App\Models\ProjectShipmentItem;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Livewire\Component as Livewire;

use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Database\Eloquent\Builder;

class ProjectShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'projectShipments';

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
                            S\Section::make(__('Import'))
                                ->schema([
                                    ...static::shipmentImportFields()
                                ])
                                ->compact()
                                ->collapsible()
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->columnSpanFull(),


                            S\Section::make(__('Export'))
                                ->schema([
                                    //
                                ])
                                ->compact()
                                ->collapsible()
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->columnSpanFull(),
                        ])
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(fn(): string => __('Shipment'))
            ->columns([
                __index(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->modal()->slideOver(),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->modal()->slideOver(),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    // Helpers
    public static function shipmentImportFields(): array
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


            S\Flex::make([
                F\Select::make('port_id')
                    ->label(__('Port'))
                    ->relationship(
                        name: 'port',
                        titleAttribute: 'port_name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->import_port_id)
                    ->required(function ($livewire): bool {
                        /** @var Project $order */
                        $order = $livewire->getOwnerRecord();
                        return !$order?->is_skip_invoice && $order?->is_foreign;
                    }),

                __number_field('exchange_rate')
                    ->label(__('Exchange Rate'))
                    ->suffix(JsContent::make(<<<'JS'
                                $get('currency') && $get('currency') !== 'VND' ? 'VND/' + $get('currency') : null
                            JS))
                    ->prefixActions([
                        A\Action::make('getExchangeRate')
                            ->label(__('Get Rate'))
                            ->icon(Heroicon::Banknotes)
                            ->action(fn($get, $set) => static::getExchangeRate($get, $set))
                            ->link()
                            ->disabled(fn($get): bool => $get('currency') === 'VND')
                    ]),

                F\Toggle::make('is_exchange_rate_final')->label(__('Final Rate?'))
                    ->inline(false)
                    ->inlineLabel(false)
                    ->grow(false)
                    ->disabled(fn($get) => $get('currency') === 'VND'),
            ])
                ->from('sm')
                ->columnSpanFull(),

            F\TextInput::make('currency')
                ->label(__('Currency'))
                ->dehydrated(false)
                ->afterStateHydrated(fn(F\Field $component, RelationManager $livewire, ?ProjectShipment $record)
                => $component->state($record?->currency ?? $livewire->getOwnerRecord()?->currency))
                ->readOnly()
                ->hidden(),


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

            __atd_ata_fields()->columnSpanFull(),

            F\Checkbox::make('declaration_required')
                ->label(__('Declaration Required'))
                ->dehydrated(false)
                ->afterStateHydrated(function (F\Field $component, RelationManager $livewire): void {
                    $project = $livewire->getOwnerRecord();
                    $component->state($project?->is_foreign
                        && !$project?->is_skip_invoice);
                })
                ->hidden(),

            S\Group::make([
                F\TextInput::make('customs_declaration_no')
                    ->label(__('Declaration No.'))
                    ->maxLength(255),

                F\DatePicker::make('customs_declaration_date')
                    ->label(__('Declaration Date'))
                    ->placeholder('YYYY-MM-DD')
                    ->format('Y-m-d')
                    ->displayFormat('Y-m-d'),

                F\Select::make('customs_clearance_status')
                    ->label(__('Clearance Status'))
                    ->options(\App\Enums\CustomsClearanceStatusEnum::class)
                    ->required(),

                F\DatePicker::make('customs_clearance_date')
                    ->label(__('Clearance Date'))
                    ->maxDate(today()),
            ])
                ->disabled(fn($get) => !$get('declaration_required'))
                ->columns()
                ->columnSpanFull(),

        ];
    }


    public static function shipmentLines(): F\Repeater
    {
        return F\Repeater::make('projectShipmentItems')
            ->label(__('Products'))
            ->relationship()
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
                            $livewire->getOwnerRecord()?->projectItems()->pluck('product_id') ?? []
                        ),
                    )
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->required(),

                __number_field('qty')
                    ->rules([
                        fn(Get $get, ?ProjectShipmentItem $record, Livewire $livewire): \Closure =>
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
        ?ProjectShipmentItem $record
    ): void {
        $productId = (int) $state;
        $order = $livewire->getOwnerRecord();

        if ($productId && $order) {
            // Tính tổng qty đã giao từ bảng project_shipment_items,
            // cho tất cả shipment thuộc order này, loại trừ record hiện tại (nếu có).
            $shippedQty = ProjectShipmentItem::whereHas('projectShipment', function (Builder $q) use ($order) {
                $q->where('project_id', $order->id);
            })
                ->where('product_id', $productId)
                ->when($record?->id, fn($q, $rid) => $q->where('id', '!=', $rid))
                ->sum('qty');

            $orderedQty = $order->projectItems()
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
        ?ProjectShipmentItem $record,
        $value,
        \Closure $fail
    ): void {
        if (!($livewire instanceof static)) {
            throw new \Exception('Component is not an instance of the expected RelationManager.');
        }

        $order = $livewire->getOwnerRecord();

        // Lấy qty trong order line cho product hiện tại
        $orderLineQty = \App\Models\ProjectItem::where('project_id', $order->id)
            ->where('product_id', $get('product_id'))
            ->first()?->qty ?? 0;

        // Tổng qty đã giao (trên tất cả project_shipment_items thuộc order), loại trừ current record
        $shippedQty = ProjectShipmentItem::whereHas('projectShipment', function (Builder $q) use ($order) {
            $q->where('project_id', $order->id);
        })
            ->where('product_id', $get('product_id'))
            ->when($record?->id, fn($q, $rid) => $q->where('id', '!=', $rid))
            ->sum('qty');

        if ($value + $shippedQty > $orderLineQty) {
            $fail(__('Remaining: :qty', ['qty' => $orderLineQty - $shippedQty]));
        }
    }

    public static function getExchangeRate(Get $get, Set $set): void
    {
        $currency = $get('currency');
        $date = $get('customs_clearance_date') ?? $get('customs_declaration_date') ?? null;
        if ($date && $currency && $currency !== 'VND') {
            $rate = \App\Services\VcbExchangeRatesService::fetch($date)[$currency][VCB_RATE_TARGET] ?? null;
            if ($rate) $rate = __number_string_converter($rate);
            if ($rate) {
                $set('exchange_rate', $rate);
            }
        } else {
            \Filament\Notifications\Notification::make()
                ->title(__('Cannot fetch exchange rate'))
                ->body(__('Please ensure that Declaration Date or Clearance Date is set.'))
                ->warning()
                ->send();
        }
    }
}
