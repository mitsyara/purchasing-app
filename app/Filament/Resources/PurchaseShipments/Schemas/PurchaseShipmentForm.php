<?php

namespace App\Filament\Resources\PurchaseShipments\Schemas;

use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager;
use App\Filament\Resources\PurchaseShipments\Pages\ManagePurchaseShipments;
use App\Filament\Resources\PurchaseShipments\PurchaseShipmentResource;
use App\Filament\Schemas\PSProductForm;
use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Support\Enums\Alignment;
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
                            ]),

                        S\Tabs\Tab::make(__('Products'))
                            ->schema(fn(PurchaseShipment $record): array => [
                                static::shipmentLines($record),
                            ])
                            ->columns(3),

                        S\Tabs\Tab::make(__('Costs & Notes'))
                            ->schema([
                                ...static::costsAndNotesFields(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function shipmentInfoFields(): array
    {
        return [
            TextEntry::make('purchase_order')
                ->inlineLabel()
                ->state(fn(?PurchaseShipment $record) => $record?->purchaseOrder?->order_number)
                ->label(__('Purchase Order'))
                ->url(fn(?PurchaseShipment $record) => $record?->purchaseOrder?->id
                    ? ViewPurchaseOrder::getUrl(['record' => $record->purchaseOrder->id]) : null, true)
                ->color('info'),

            F\ToggleButtons::make('shipment_status')
                ->label(__('Shipment Status'))
                ->options(\App\Enums\ShipmentStatusEnum::class)
                ->grouped()
                ->grow(false)
                ->columnSpanFull()
                ->required(),

            S\Flex::make([
                F\Select::make('port_id')
                    ->label(__('Port'))
                    ->relationship(
                        name: 'port',
                        titleAttribute: 'port_name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->required(fn(callable $get): bool => (bool) $get('declaration_required')),

                F\Select::make('warehouse_id')
                    ->label(__('Warehouse'))
                    ->relationship(
                        name: 'warehouse',
                        titleAttribute: 'warehouse_name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->required(),

            ])
                ->columnSpanFull(),

            F\Select::make('staff_docs_id')
                ->label(__('Docs Staff'))
                ->relationship(
                    name: 'staffDocs',
                    titleAttribute: 'name',
                )
                ->required(),

            F\TextInput::make('tracking_no')
                ->label(__('Tracking Number')),

            S\Group::make(fn(PurchaseShipment $record): array
            => __eta_etd_fields(true, $record?->purchaseOrder->is_foreign)),

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
                        )
                        ->required(fn(callable $get): bool
                        => (bool) $get('declaration_required')),

                    F\Select::make('staff_declarant_processing_id')
                        ->label(__('Processing Staff'))
                        ->relationship(
                            name: 'staffDeclarantProcessing',
                            titleAttribute: 'name',
                        )
                        ->required(fn(callable $get): bool
                        => (bool) $get('declaration_required')),

                    F\TextInput::make('currency')
                        ->label(__('Currency'))
                        ->dehydrated(false)
                        ->afterStateHydrated(fn(F\Field $component, ?PurchaseShipment $record)
                        => $component->state($record?->currency ?? $record?->purchaseOrder?->currency))
                        ->readOnly()
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
                                ->disabled(fn($get): bool => $get('currency') === 'VND')
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

    public static function shipmentLines(PurchaseShipment $record): F\Repeater
    {
        return F\Repeater::make('purchaseShipmentLines')
            ->label(__('Products'))
            ->relationship('purchaseShipmentLines')
            ->hiddenLabel()
            ->schema([
                __number_field('break_price')
                    ->label(__('Break Price'))
                    ->inlineLabel()
                    ->suffix('VND'),

                F\Hidden::make('product_life_cycle')
                    ->afterStateHydrated(fn(F\Field $component, ?PurchaseShipmentLine $record)
                    => $component->state($record?->product?->product_life_cycle))
                    ->dehydrated(false),
                F\Hidden::make('uom')
                    ->afterStateHydrated(fn(F\Field $component, ?PurchaseShipmentLine $record)
                    => $component->state($record?->product?->product_uom))
                    ->dehydrated(false),

                F\Repeater::make('transactions')
                    ->hiddenLabel()
                    ->relationship()
                    ->table([
                        F\Repeater\TableColumn::make('Lot/Batch No.')
                            ->markAsRequired(),
                        F\Repeater\TableColumn::make(fn() => static::getUomJs())
                            ->width('130px')
                            ->markAsRequired(),
                        F\Repeater\TableColumn::make('Mfg Date')
                            ->width('130px')
                            ->markAsRequired(),
                        F\Repeater\TableColumn::make('Exp Date')
                            ->width('130px')
                            ->markAsRequired(),
                    ])
                    ->schema([
                        F\TextInput::make('lot_no')
                            ->label(__('Lot No.'))
                            ->required(),

                        __number_field('qty')
                            ->label(__('Quantity'))
                            ->rules([
                                fn(Get $get, F\Field $component): \Closure
                                => function (string $attribute, $value, \Closure $fail) use ($get, $component) {
                                    $availableQty = (float) $get('../../qty'); // tổng qty còn lại
                                    $transactions = $get('../') ?? []; // tất cả dòng trong repeater
                                    $key = \Illuminate\Support\Str::of($component->getStatePath())
                                        ->after($component->getParentRepeater()->getStatePath() . '.')
                                        ->before('.')
                                        ->toString();

                                    $sumQty = 0;
                                    foreach ($transactions as $uuid => $transaction) {
                                        $sumQty += __number_string_converter($transaction['qty'] ?? 0, false);
                                        // dừng khi tới dòng hiện tại
                                        if ($uuid === $key) break;
                                    }

                                    if ($sumQty > $availableQty) {
                                        $fail(__("Total qty cannot exceed :qty.", ['qty' => $availableQty]));
                                    }
                                },
                            ])
                            ->required(),

                        F\DatePicker::make('mfg_date')
                            ->label(__('Mfg Date'))
                            ->maxDate(today())
                            ->afterStateUpdatedJs(fn() => static::setProductExpDateByJs())
                            ->required(),

                        F\DatePicker::make('exp_date')
                            ->label(__('Exp Date'))
                            ->required(),

                    ])
                    ->defaultItems(1)
                    ->addActionLabel(__('Add Lot/Batch'))
                    ->compact()
                    ->columnSpanFull(),
            ])
            ->itemLabel(fn(array $state) => static::getShipmentLineLabel($state))
            ->addable(false)
            ->deletable(false)
            ->minItems(1)
            ->columns()
            ->columnSpanFull()
            ->collapsible()
        ;
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

    public static function viewOrderAction(): Action
    {
        return Action::make('viewOrder')
            ->icon(fn(Action $action)
            => match ($action->isDisabled()) {
                default => Heroicon::Eye,
                true => Heroicon::EyeSlash,
            })
            ->url(fn($get) => $get('purchase_order_id')
                ? ViewPurchaseOrder::getUrl(['record' => (int) $get('purchase_order_id')])
                : null, true);
    }

    // Helper functions
    public static function getShipmentLineLabel(array $state): string
    {
        $product = \App\Models\Product::find($state['product_id']);
        $productName = $product?->product_full_name ?? __('(Select Product)');
        $uom = $product?->product_uom;
        $unitPrice = \Illuminate\Support\Number::currency(($state['unit_price'] ?? 0),
            $state['currency'] ?? 'VND',
            locale: app()->getLocale()
        );
        $qty = __number_string_converter($state['qty'] ?? 0);
        return "{$productName} " . SPACING . " Qty: {$qty} {$uom} " . SPACING . " Price: {$unitPrice}";
    }

    public static function getExchangeRate(Get $get, Set $set): void
    {
        $currency = $get('currency');
        $date = $get('customs_clearance_date') ?? $get('customs_declaration_date') ?? null;
        if ($date && $currency && $currency !== 'VND') {
            $exchangeRateService = app(\App\Services\Core\ExchangeRateService::class);
            $rate = $exchangeRateService->getRate($currency, 'VND', $date);
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

    // Js helpers
    public static function getUomJs(): JsContent
    {
        return JsContent::make(<<<'JS'
            $get('uom') ? 'Qty (' + $get('uom') + ')' : 'Qty';
        JS);
    }
    public static function setProductExpDateByJs(): string
    {
        return <<<'JS'
            if (!$state) {
                // không có ngày sản xuất, bỏ qua
            } else {
                const [year, month, day] = $state.split('-').map(Number);
                const mfgDate = new Date(year, month - 1, day);
                const lifeCycle = parseInt($get('../../product_life_cycle') ?? 0, 10);
                const expDate = new Date(mfgDate);

                if (lifeCycle > 0) {
                    expDate.setDate(expDate.getDate() + lifeCycle);
                    $set('exp_date', expDate.toISOString().split("T")[0]);
                }
            }
        JS;
    }
}
