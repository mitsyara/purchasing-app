<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Schemas\POProductForm;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrder\PurchaseOrderService;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Flex::make([
                    S\Group::make([
                        S\Section::make()
                            ->schema([
                                ...static::orderInfoFields(),
                                ...static::staffInfoFields(),
                                ...__eta_etd_fields(),
                            ])
                            ->columns(),
                    ]),

                    S\Section::make()
                        ->schema([
                            ...static::generalFields(),
                        ])
                        ->grow(false)
                        ->columns()
                        ->columnOrder([
                            'md' => 0,
                            'lg' => 1,
                        ]),
                ])
                    ->from('2xl')
                    ->columnSpanFull(),

                // Products
                S\Section::make(__('Products'))
                    ->schema([
                        F\Repeater::make('purchaseOrderLines')
                            ->relationship()
                            ->hiddenLabel()
                            ->table([
                                ...POProductForm::repeaterHeaders(),
                            ])
                            ->schema([
                                ...POProductForm::formSchema(),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel(__('Add Product'))
                            ->columnSpanFull()
                            ->columns(3),
                    ])
                    ->visibleOn(['create'])
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public static function orderInfoFields(): array
    {
        return [
            F\Select::make('company_id')
                ->label(__('Company'))
                ->relationship(
                    name: 'company',
                    titleAttribute: 'company_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->orderBy('id', 'asc'),
                )
                ->required(),

            S\Flex::make([
                S\FusedGroup::make([
                    F\Select::make('before_after')
                        ->options([
                            'before' => __('Before'),
                            'after' => __('After'),
                        ])
                        ->selectablePlaceholder(false)
                        ->default('after')
                        ->grow(false)
                        ->dehydrated(false)
                        ->afterStateHydrated(fn(F\Field $component, $get)
                        => (int) $get('pay_term_days') >= 0
                            ? $component->state('after')
                            : $component->state('before')),

                    F\Select::make('pay_term_delay_at')
                        ->label(__('Payment Term Delay At'))
                        ->options(\App\Enums\PaytermDelayAtEnum::class)
                        ->selectablePlaceholder(false),

                    F\TextInput::make('pay_term_days')
                        ->label(__('Payment Term Days'))
                        ->suffix(__('Days'))
                        ->afterStateHydrated(fn(F\Field $component, $state)
                        => $component->state(abs($state)))
                        ->dehydrateStateUsing(fn($state, $get)
                        => $get('before_after') === 'before' ? -abs($state) : abs($state))
                        ->integer()
                        ->datalist([0, 30, 60]),
                ])
                    ->label(__('Payment Terms'))
                    ->columns([
                        'default' => 3,
                    ]),

                F\TextInput::make('payment_method')
                    ->label('Payment Method')
                    ->extraInputAttributes([
                        'class' => 'lg:max-w-[80px]',
                    ])
                    ->grow(false),
            ])
                ->from('lg'),

            F\Select::make('supplier_id')
                ->label(__('Supplier'))
                ->relationship(
                    name: 'supplier',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true)->whereNotNull('contact_name'),
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_contract_id') === (int) $value)
                ->preload()
                ->searchable()
                ->createOptionForm(ContactForm::configure(new Schema())->getComponents())
                ->createOptionAction(fn(Action $action): Action
                => $action->slideOver())
                ->editOptionForm(ContactForm::configure(new Schema())->getComponents())
                ->editOptionAction(fn(Action $action): Action
                => $action->slideOver())
                ->columnSpanFull()
                ->required(),

            F\Select::make('supplier_contract_id')
                ->label(__('Contract Supplier'))
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'supplierContract',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true)->whereNotNull('contact_name'),
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value
                    || (int) $get('supplier_payment_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull(),

            F\Select::make('supplier_payment_id')
                ->label(__('Payment Receiver'))
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'supplierPayment',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true)->whereNotNull('contact_name'),
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value
                    || (int) $get('supplier_contract_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull(),

        ];
    }

    public static function staffInfoFields(): array
    {
        return [
            S\Group::make([
                F\Select::make('staff_buy_id')
                    ->label(__('Purchaser'))
                    ->relationship(
                        name: 'staffBuy',
                        titleAttribute: 'name',
                    )
                    ->preload()
                    ->searchable()
                    ->required(),

                F\Select::make('staff_sales_id')
                    ->label(__('Salesperson'))
                    ->relationship(
                        name: 'staffSales',
                        titleAttribute: 'name',
                    )
                    ->preload()
                    ->searchable(),

                F\Select::make('staff_docs_id')
                    ->label(__('Clearance Docs staff'))
                    ->relationship(
                        name: 'staffDocs',
                        titleAttribute: 'name',
                    )
                    ->preload()
                    ->searchable(),

                F\Select::make('staff_declarant_id')
                    ->label(__('Declarant staff'))
                    ->relationship(
                        name: 'staffDeclarant',
                        titleAttribute: 'name',
                    )
                    ->preload()
                    ->searchable(),
            ])
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 4,
                ])
                ->columnSpanFull(),


        ];
    }

    public static function generalFields(): array
    {
        return [
            F\ToggleButtons::make('order_status')
                ->label(__('Order Status'))
                ->options(\App\Enums\OrderStatusEnum::class)
                ->default(\App\Enums\OrderStatusEnum::Draft)
                ->disableOptionWhen(fn($value, $operation): bool
                => $operation === 'create'
                    && $value === \App\Enums\OrderStatusEnum::Canceled->value)
                ->grouped()
                ->columnSpanFull()
                ->required(),

            F\TextInput::make('order_number')
                ->label(__('Order Number'))
                ->unique()
                ->requiredIf('order_status', [
                    \App\Enums\OrderStatusEnum::Inprogress->value,
                    \App\Enums\OrderStatusEnum::Completed->value,
                ])
                ->validationMessages([
                    'required_if' => __('Order Number is required!')
                ])
                ->suffixAction(
                    Action::make('generate')
                        ->label(__('Generate Order Number'))
                        ->icon(Heroicon::OutlinedPlay)
                        ->action(function (callable $set, callable $get, ?PurchaseOrder $record) {
                            $purchaseOrderService = app(PurchaseOrderService::class);
                            // Nếu chưa có date, set date là hôm nay
                            if ($get('company_id') && $get('supplier_id')) {
                                if (!$get('order_date')) $set('order_date', today());
                            }

                            // Tạo số order
                            $orderNumber = $purchaseOrderService->generateOrderNumber([
                                'company_id' => $get('company_id'),
                                'order_date' => $get('order_date'),
                                'supplier_id' => $get('supplier_id'),
                            ], $record?->id);
                            // Set số order vào form
                            $set('order_number', $orderNumber);
                        })
                        ->color('info')
                )
                ->hintAction(
                    Action::make('resetOrderNumber')
                        ->label(__('Reset'))
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->action(function (callable $set, ?PurchaseOrder $record) {
                            $set('order_number', $record?->order_number);
                        })
                        ->color('secondary')
                        ->disabled(fn(?PurchaseOrder $record) => !$record)
                ),

            F\DatePicker::make('order_date')
                ->label(__('Order Date'))
                ->minDate(today()->subMonths(6))
                ->maxDate(today()),

            F\Select::make('import_warehouse_id')
                ->label(__('Import Warehouse'))
                ->relationship(
                    name: 'importWarehouse',
                    titleAttribute: 'warehouse_name',
                ),

            F\Select::make('import_port_id')
                ->label(__('Import Port'))
                ->relationship(
                    name: 'importPort',
                    titleAttribute: 'port_name',
                ),

            F\Select::make('incoterm')
                ->label(__('Incoterm'))
                ->options(\App\Enums\IncotermEnum::class)
                ->default(\App\Enums\IncotermEnum::CIF),

            F\Select::make('currency')
                ->label(__('Currency'))
                ->options(fn() => \App\Models\Country::whereIsFav(true)->pluck('curr_name', 'curr_code'))
                ->default(fn() => 'USD')
                ->required(),

            F\Checkbox::make('is_skip_invoice')
                ->label(__('Skip Invoice'))
                ->default(false)
                ->columnSpanFull(),

            __notes()
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    // Helpers
}
