<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Filament\Schemas\POProductForm;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
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
                    ->from('xl')
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
                                ...POProductForm::configure(new Schema())->getComponents(),
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

            F\Select::make('supplier_id')
                ->label(__('Supplier'))
                ->relationship(
                    name: 'supplier',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true),
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_contract_id') === (int) $value)
                ->preload()
                ->searchable()
                ->required(),

            F\Select::make('supplier_contract_id')
                ->label(__('Contract Supplier'))
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'supplierContract',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true),
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value
                    || (int) $get('supplier_payment_id') === (int) $value)
                ->preload()
                ->searchable(),

            F\Select::make('supplier_payment_id')
                ->label(__('Payment Receiver'))
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'supplierPayment',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true),
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value
                    || (int) $get('supplier_contract_id') === (int) $value)
                ->preload()
                ->searchable(),

            F\Select::make('end_user_id')
                ->label(__('End User'))
                ->afterLabel(__('* If applicable'))
                ->relationship(
                    name: 'endUser',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_cus', true),
                )
                ->preload()
                ->searchable()
                ->extraAlpineAttributes([
                    'x-init' => <<<'JS'
                        window.addEventListener('toggle-end-user', event => {
                            const { disabled } = event.detail ?? {};
                            if (typeof select === 'undefined') return;
                            disabled ? select.disable() : select.enable();
                        })
                    JS,
                ]),

            S\Group::make([
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
                        ->options(\App\Enums\PayTermDelayAtEnum::class)
                        ->grow(false),

                    F\TextInput::make('pay_term_days')
                        ->label(__('Payment Term Days'))
                        ->suffix(__('Days'))
                        ->afterStateHydrated(fn(F\Field $component, $state)
                        => $component->state(abs($state)))
                        ->dehydrateStateUsing(fn($state, $get)
                        => $get('before_after') === 'before' ? -abs($state) : abs($state))
                        ->integer()
                        ->datalist([0, 30, 60])
                        ->grow(false),

                ])
                    ->label(__('Payment Terms'))
                    ->columns(['default' => 3]),
            ])
                ->columns()
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
            ])
                ->columns(),

            S\Group::make([
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
                ->columns(),


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
                ]),

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
                ->default(\App\Enums\IncotermEnum::CIF)
                ->extraInputAttributes([
                    'x-init' => <<<'JS'
                        $watch('$state', value => {
                            window.dispatchEvent(
                                new CustomEvent('toggle-end-user', {
                                    detail: { disabled: value !== 'CIF' }
                                })
                            )
                        })
                    JS,
                ]),

            F\Select::make('currency')
                ->label(__('Currency'))
                ->options(fn() => \App\Models\Country::whereIsFav(true)->pluck('curr_name', 'curr_code'))
                ->default(fn() => 'USD')
                ->required(),

            F\Checkbox::make('is_skip_invoice')
                ->label(__('Skip Invoice'))
                ->default(false)
                ->columnSpanFull(),

        ];
    }

    // Helpers
}
