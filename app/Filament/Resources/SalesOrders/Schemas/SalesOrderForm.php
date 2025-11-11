<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Schemas\POProductForm;
use App\Models\SalesOrder;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\JsContent;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Flex::make([
                    S\Section::make()
                        ->schema([
                            ...static::orderInfoFields(),
                        ])
                        ->columns(),

                    S\Section::make()
                        ->schema([
                            ...static::generalFields(),
                        ])
                        ->grow(false),
                ])
                    ->from('2xl')
                    ->columnSpanFull(),

                // Đợt giao hàng
                S\Section::make(__('Delivery Schedules'))
                    ->schema([
                        ...static::deliveryScheduleForm(),
                    ])
                    ->columnSpanFull(),

            ]);
    }

    // Helpers
    public static function orderInfoFields(): array
    {
        return [
            // seller
            F\Select::make('company_id')
                ->relationship(
                    name: 'company',
                    titleAttribute: 'company_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                ),

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

            // customer
            F\Select::make('customer_id')
                ->relationship(
                    name: 'customer',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder
                    => $query->where('is_cus', true)
                )
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

            // contract customer
            F\Select::make('customer_contract_id')
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'customerContract',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder
                    => $query->where('is_cus', true)
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('customer_id') === (int) $value
                    || (int) $get('customer_payment_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull(),

            // money receiver
            F\Select::make('customer_payment_id')
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'customerPayment',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder
                    => $query->where('is_cus', true)
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('customer_id') === (int) $value
                    || (int) $get('customer_contract_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull(),

        ];
    }


    public static function generalFields(): array
    {
        return [
            S\Flex::make([
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

                F\Select::make('currency')
                    ->label(__('Currency'))
                    ->options(fn() => \App\Models\Country::whereIsFav(true)->pluck('curr_code', 'curr_code'))
                    ->default(fn() => 'VND')
                    ->grow(false)
                    ->required(),
            ]),

            S\Group::make([
                F\DatePicker::make('order_date')
                    ->label(__('Order Date'))
                    ->minDate(today()->subMonths(6))
                    ->maxDate(today())
                    ->grow(false),

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
                            ->action(function (callable $set, callable $get, ?SalesOrder $record) {
                                // Nếu chưa có date, set date là hôm nay
                                if ($get('company_id') && $get('supplier_id')) {
                                    if (!$get('order_date')) $set('order_date', today());
                                }

                                // TODO: Làm service cho đơn bán SalesOrder
                            })
                            ->color('info')
                    )
                    ->hintAction(
                        Action::make('resetOrderNumber')
                            ->label(__('Reset'))
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->action(function (callable $set, ?SalesOrder $record) {
                                $set('order_number', $record?->order_number);
                            })
                            ->color('secondary')
                            ->disabled(fn(?SalesOrder $record) => !$record)
                    ),
            ])
                ->columns(),


            S\Flex::make([
                F\Select::make('staff_sales_id')
                    ->label(__('Sales Staff'))
                    ->relationship(
                        name: 'staffSales',
                        titleAttribute: 'name',
                    )
                    ->preload()
                    ->searchable()
                    ->required(),

                F\Select::make('export_warehouse_id')
                    ->relationship(
                        name: 'warehouse',
                        titleAttribute: 'warehouse_name',
                    ),
            ]),

            F\Checkbox::make('is_skip_invoice')
                ->default(false),

            __notes()
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    /**
     * Đợt giao dự kiến, bỏ qua sản phẩm (tính tổng tự gắn vào)
     */
    public static function deliveryScheduleForm(): array
    {
        $errorMessage = 'At least one Date is required.';

        return [
            F\Repeater::make('deliverySchedules')
                ->hiddenLabel()
                ->relationship()
                ->schema([
                    S\Flex::make([

                        F\DatePicker::make('from_date')
                            ->label(__('From Date'))
                            ->minDate(today()->subMonths(6))
                            ->requiredWithout('to_date')
                            ->validationMessages([
                                'required_without' => __($errorMessage),
                            ])
                            ->grow(false),

                        F\DatePicker::make('to_date')
                            ->label(__('To Date'))
                            ->minDate(today()->subMonths(6))
                            ->requiredWithout('from_date')
                            ->validationMessages([
                                'required_without' => __($errorMessage),
                            ])
                            ->grow(false),

                        F\TextInput::make('delivery_address')
                            ->label(__('Address')),

                        // __notes()
                        //     ->hint(__('Delivery notes'))
                        //     ->rows(4)
                        //     ->columnSpanFull(),
                    ])
                        ->columns(),

                    // Product list
                    F\Repeater::make('deliveryLines')
                        ->relationship()
                        ->hiddenLabel()
                        ->table(POProductForm::repeaterHeaders())
                        ->schema([
                            ...POProductForm::configure(new Schema())->getComponents(),
                        ])
                        ->compact()
                        ->addActionLabel(__('Add Product'))

                ])
                ->itemLabel(__('Shipment'))
                ->itemNumbers()
                ->addActionLabel(__('Add Shipment'))
                // ->collapsible()
                ->columns(1)
                ->defaultItems(1)
                ->minItems(1)
                ->columnSpanFull(),
        ];
    }
}
