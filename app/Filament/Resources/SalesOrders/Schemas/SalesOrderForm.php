<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Schemas\POProductForm;
use App\Models\SalesOrder;
use DefStudio\SearchableInput\Forms\Components\SearchableInput;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                S\Section::make(__('Products'))
                    ->schema([
                        ...static::orderLines(),
                    ])
                    ->visible(fn(string $operation) => $operation === 'create')
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

                F\Select::make('currency')
                    ->label(__('Currency'))
                    ->options(fn() => \App\Models\Country::whereIsFav(true)->pluck('curr_code', 'curr_code'))
                    ->default(fn() => 'VND')
                    ->grow(false)
                    ->required(),
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
            F\ToggleButtons::make('order_status')
                ->label(__('Order Status'))
                ->options(\App\Enums\OrderStatusEnum::class)
                ->default(\App\Enums\OrderStatusEnum::Draft)
                ->disableOptionWhen(fn($value, $operation): bool
                => $operation === 'create'
                    && $value === \App\Enums\OrderStatusEnum::Canceled->value)
                ->grouped()
                ->required(),

            S\Flex::make([
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
                                if ($get('company_id') && $get('customer_id')) {
                                    if (!$get('order_date')) {
                                        $date = today()->format('Y-m-d');
                                        $set('order_date', $date);
                                    } else {
                                        $date = $get('order_date');
                                    }
                                }
                                // TODO: Làm service cho đơn bán SalesOrder
                                $service = app(\App\Services\SalesOrder\SalesOrderService::class);
                                // Tạo số order
                                $orderNumber = $service->generateOrderNumber([
                                    'company_id' => $get('company_id'),
                                    'order_date' => $date,
                                    'customer_id' => $get('customer_id'),
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
                            ->action(function (callable $set, ?SalesOrder $record) {
                                $set('order_number', $record?->order_number);
                            })
                            ->color('secondary')
                            ->disabled(fn(?SalesOrder $record) => !$record)
                    ),
            ]),

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
                    ->label(__('Warehouse'))
                    ->relationship(
                        name: 'warehouse',
                        titleAttribute: 'warehouse_name',
                    )
                    ->grow(false),
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
    public static function orderLines(): array
    {
        return [
            F\Repeater::make('salesOrderLines')
                ->hiddenLabel()
                ->relationship()
                ->table(POProductForm::repeaterHeaders())
                ->schema(POProductForm::formSchema())
                ->addActionLabel(__('Add Product'))
                ->columns(1)
                ->defaultItems(1)
                ->minItems(1)
                ->compact()
                ->columnSpanFull(),
        ];
    }
}
