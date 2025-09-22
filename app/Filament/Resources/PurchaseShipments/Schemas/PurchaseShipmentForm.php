<?php

namespace App\Filament\Resources\PurchaseShipments\Schemas;

use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseShipments\Pages\ManagePurchaseShipments;
use App\Filament\Resources\PurchaseShipments\PurchaseShipmentResource;
use App\Filament\Schemas\PSProductForm;
use App\Models\PurchaseShipment;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class PurchaseShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                S\Tabs::make('Shipment')
                    ->tabs([
                        S\Tabs\Tab::make(__('Shipment Info'))
                            ->schema([
                                ...static::shipmentInfoFields(),
                            ])
                            ->columns(),

                        S\Tabs\Tab::make(__('Clearance & Exchange Rate'))
                            ->schema([
                                ...static::clearanceAndExchangeRateFields(),
                            ])
                            ->disabled(fn(callable $get) => !$get('purchase_order_id')),

                        S\Tabs\Tab::make(__('Products'))
                            ->schema([
                                ...static::shipmentLines(),
                            ])
                            ->disabled(fn(callable $get) => !$get('purchase_order_id')),

                        S\Tabs\Tab::make(__('Costs & Notes'))
                            ->schema([
                                ...static::costsAndNotes(),
                            ])
                            ->disabled(fn(callable $get) => !$get('purchase_order_id')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function shipmentInfoFields(): array
    {
        return [
            S\Flex::make([
                F\Select::make('purchase_order_id')
                    ->label(__('Purchase Order'))
                    ->relationship(
                        name: 'purchaseOrder',
                        titleAttribute: 'order_number',
                        modifyQueryUsing: function (Builder $query, string $operation): Builder {
                            // TODO: calculate only orders which are not fully shipped
                            $query = $query
                                ->where('order_status', \App\Enums\OrderStatusEnum::Inprogress->value)
                                ->whereNotNull('order_number');

                            return $operation === 'create'
                                ? $query->incompleteShipmentsQty()
                                : $query;
                        }
                    )
                    ->visibleOn([
                        PurchaseShipmentResource::class,
                        ManagePurchaseShipments::class,
                    ])
                    ->afterStateUpdatedJs(<<<'JS'
                        !$state ? $set('port_id', null) : null;
                    JS)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn($get, $set)
                    => static::populateShipmentFieldsFromOrder($get, $set))
                    ->suffixAction(Action::make('viewOrder')
                        ->icon(fn(Action $action)
                        => match ($action->isDisabled()) {
                            default => Heroicon::Eye,
                            true => Heroicon::EyeSlash,
                        })
                        ->url(fn($get) => $get('purchase_order_id')
                            ? ViewPurchaseOrder::getUrl(['record' => (int) $get('purchase_order_id')])
                            : null, true)
                        ->disabled(fn($get) => !$get('purchase_order_id')))
                    ->required(),

                F\ToggleButtons::make('shipment_status')
                    ->label(__('Shipment Status'))
                    ->options(\App\Enums\ShipmentStatusEnum::class)
                    ->default(\App\Enums\ShipmentStatusEnum::Pending->value)
                    ->grouped()
                    ->grow(false)
                    ->disableOptionWhen(fn(string $value, string $operation): bool
                    => $operation === 'create'
                        && $value === \App\Enums\ShipmentStatusEnum::Cancelled->value)
                    ->required(),

            ])
                ->columnSpanFull(),

            F\Select::make('port_id')
                ->label(__('Port'))
                ->relationship(
                    name: 'port',
                    titleAttribute: 'port_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->required(function (callable $get): bool {
                    /** @var \App\Models\PurchaseOrder $order */
                    $order = \App\Models\PurchaseOrder::find($get('purchase_order_id'));
                    return !$order?->is_skip_invoice && $order?->is_foreign;
                }),

            F\Select::make('warehouse_id')
                ->label(__('Warehouse'))
                ->relationship(
                    name: 'warehouse',
                    titleAttribute: 'warehouse_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->required(),

            F\Select::make('staff_docs_id')
                ->label(__('Docs Staff'))
                ->relationship(
                    name: 'staffDocs',
                    titleAttribute: 'name',
                )
                ->searchable()
                ->preload(),

            F\TextInput::make('tracking_no')
                ->label(__('Tracking Number'))
                ->maxLength(255),

            ...__eta_etd_fields(true),

            S\Fieldset::make(__('Actual Arrival/Departure'))
                ->schema([
                    __atd_ata_fields()->columnSpanFull(),
                ]),
        ];
    }

    public static function clearanceAndExchangeRateFields(): array
    {
        return [
            S\Fieldset::make(__('Staff In Charge'))
                ->schema([
                    F\Select::make('staff_declarant_id')
                        ->label(__('Declarant'))
                        ->relationship(
                            name: 'staffDeclarant',
                            titleAttribute: 'name',
                        ),

                    F\Select::make('staff_declarant_processing_id')
                        ->label(__('Processing Staff'))
                        ->relationship(
                            name: 'staffDeclarantProcessing',
                            titleAttribute: 'name',
                        ),

                    F\TextInput::make('currency')
                        ->label(__('Currency'))
                        ->dehydrated(false)
                        ->afterStateHydrated(fn(F\Field $component, ?PurchaseShipment $record)
                        => $component->state($record?->currency ?? $record?->purchaseOrder?->currency))
                        ->readOnly()
                        ->grow(false)

                        ->hidden(),

                    __number_field('exchange_rate')
                        ->label(__('Exchange Rate'))
                        ->suffix(JsContent::make(<<<'JS'
                            $get('currency') && $get('currency') !== 'VND' ? 'VND/' + $get('currency') : null
                        JS))
                        ->prefixActions([
                            Action::make('getExchangeRate')
                                ->label(__('Get Rate'))
                                ->icon(Heroicon::Banknotes)
                                ->action(fn($get, $set) => static::getExchangeRate($get, $set))
                                ->link()
                        ]),

                    F\Toggle::make('is_exchange_rate_final')->label(__('Final Rate?'))
                        ->inline(false)
                        ->inlineLabel(false)
                        ->disabled(fn($get) => $get('currency') === 'VND'),

                ])
                ->columns()
                ->columnSpanFull(),

            S\Fieldset::make('Customs Declaration')
                ->schema([
                    F\Checkbox::make('declaration_required')
                        ->label(__('Declaration Required'))
                        ->dehydrated(false)
                        ->afterStateHydrated(fn(F\Field $component, ?PurchaseShipment $record)
                        => $component->state($record?->purchaseOrder?->is_foreign
                            && !$record?->purchaseOrder?->is_skip_invoice))
                        ->hidden(),

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
                ->columns()
                ->columnSpanFull()
                ->disabled(fn($get) => !$get('declaration_required')),
        ];
    }

    public static function shipmentLines(): array
    {
        return [
            F\Repeater::make('purchaseShipmentLines')
                ->label(__('Products'))
                ->relationship('purchaseShipmentLines')
                ->hiddenLabel()
                ->table([
                    ...PSProductForm::repeaterHeaders(),
                ])
                ->schema([
                    ...PSProductForm::configure(new Schema())->getComponents(),
                ])
                ->minItems(1)
                ->itemLabel(function (array $state): string {
                    $productName = $state['product_id']
                        ? \App\Models\Product::find($state['product_id'])?->product_full_name
                        : __('(Select Product)');
                    $qty = $state['qty'] ?? 0;
                    return "{$productName} - Qty: {$qty}";
                })
                ->columnSpanFull()
                ->addActionLabel(__('Add Product'))
                ->required(),
        ];
    }

    public static function costsAndNotes(): array
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
                        ->grid(4)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            __notes()
                ->rows(5),
        ];
    }

    // Helper functions

    public static function populateShipmentFieldsFromOrder(Get $get, Set $set): void
    {
        /** @var \App\Models\PurchaseOrder $order */
        $order = \App\Models\PurchaseOrder::find($get('purchase_order_id'));

        // Virtual fields
        $set('declaration_required', $order?->is_foreign && !$order?->is_skip_invoice ? true : false);

        // Actual fields
        $fields = [
            'port_id',
            'warehouse_id',
            'currency',
            'staff_buy_id',
            'staff_docs_id',
            'staff_declarant_id',
            'staff_declarant_processing_id',
            'etd_min',
            'etd_max',
            'eta_min',
            'eta_max',
        ];
        foreach ($fields as $field) {
            if ($field === 'port_id' || $field === 'warehouse_id') {
                $set($field, $order->{'import_' . $field} ?? null);
            } else {
                $set($field, $order->$field ?? null);
            }
        }
    }

    public static function getExchangeRate(Get $get, Set $set): void
    {
        $currency = $get('currency');
        $date = $get('customs_clearance_date') ?? $get('customs_declaration_date') ?? null;
        if ($date && $currency && $currency !== 'VND') {
            $rate = \App\Services\VcbExchangeRatesService::fetch($date)[$currency][VCB_RATE_TARGET] ?? null;
            if ($rate) $rate = __number_string_converter_vi($rate);
            $set('exchange_rate', $rate);
        }
    }
}
