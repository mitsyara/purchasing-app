<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Schemas\POProductForm;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Illuminate\Database\Eloquent\Builder;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::getForm());
    }

    public static function getForm(): array
    {
        return [
            S\Flex::make([
                S\Section::make()
                    ->schema(static::formLeftSide())
                    ->columns(),

                S\Section::make()
                    ->schema(static::formRightSide())
                    ->grow(false)
                    ->columns(['default' => 2]),
            ])
                ->from('xl')
                ->columnSpanFull(),

            // Products
            S\Section::make(__('Products'))
                ->schema(static::projectProducts())
                ->visible(fn($operation) => $operation === 'create')
                ->columnSpanFull(),
        ];
    }

    public static function formLeftSide(): array
    {
        return [
            F\Select::make('company_id')
                ->label('Company')
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
                        ->selectablePlaceholder(false)
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
                ->label('Supplier')
                ->relationship(
                    name: 'supplier',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true)
                )
                ->preload()
                ->searchable()
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_contract_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull()
                ->required(),

            F\Select::make('end_user_id')
                ->label('End User')
                ->relationship(
                    name: 'endUser',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_cus', true)
                )
                ->preload()
                ->searchable()
                ->columnSpanFull()
                ->required(),

            F\Select::make('supplier_contract_id')
                ->label('Supplier Contract')
                ->relationship(
                    name: 'supplierContract',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true)
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value
                    || (int) $get('supplier_payment_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull(),

            F\Select::make('supplier_payment_id')
                ->label('Supplier Payment')
                ->relationship(
                    name: 'supplierPayment',
                    titleAttribute: 'contact_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true)
                )
                ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value
                    || (int) $get('supplier_contract_id') === (int) $value)
                ->preload()
                ->searchable()
                ->columnSpanFull(),

            // Staffs
            S\Flex::make([
                F\Select::make('staff_buy_id')
                    ->label('Buying Staff')
                    ->relationship(
                        name: 'staffBuy',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->required(),

                // F\Select::make('staff_approved_id')
                //     ->label('Approved Staff')
                //     ->relationship(
                //         name: 'staffApproved',
                //         titleAttribute: 'name',
                //         modifyQueryUsing: fn(Builder $query): Builder => $query
                //     ),

                F\Select::make('staff_docs_id')
                    ->label('Documentation Staff')
                    ->relationship(
                        name: 'staffDocs',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    ),

                F\Select::make('staff_declarant_id')
                    ->label('Customs Declarant Staff')
                    ->relationship(
                        name: 'staffDeclarant',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    ),

                F\Select::make('staff_sales_id')
                    ->label('Sales Staff')
                    ->relationship(
                        name: 'staffSales',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->required(),

            ])
                ->columnSpanFull(),

            ...__eta_etd_fields(),
        ];
    }

    public static function formRightSide(): array
    {
        return [
            F\ToggleButtons::make('project_status')
                ->label('Project Status')
                ->options(\App\Enums\OrderStatusEnum::class)
                ->grouped()
                ->columnSpanFull(),

            F\DatePicker::make('project_date')
                ->label('Project Date')
                ->maxDate(today()),

            F\TextInput::make('project_number')
                ->label('Project Number')
                ->unique()
                ->requiredIf('project_status', [
                    \App\Enums\OrderStatusEnum::Inprogress->value,
                    \App\Enums\OrderStatusEnum::Completed->value,
                ]),

            F\Select::make('import_port_id')
                ->label('Import Port')
                ->relationship(
                    name: 'importPort',
                    titleAttribute: 'port_name',
                    modifyQueryUsing: fn(Builder $query): Builder => $query
                )
                ->requiredIfDeclined('is_skip_invoice')
                ->validationMessages([
                    'required_if_declined' => 'Required unless Skip Invoice',
                ]),
            F\Select::make('incoterm')
                ->label('Incoterm')
                ->options(\App\Enums\IncotermEnum::class)
                ->requiredWith('import_port_id'),

            F\Select::make('currency')
                ->label(__('Currency'))
                ->options(fn() => \App\Models\Country::whereIsFav(true)->pluck('curr_name', 'curr_code'))
                ->default(fn() => 'USD')
                ->required(),

            S\Group::make([
                F\Checkbox::make('is_skip_invoice')
                    ->label('Skip Invoice?')
                    ->default(false),
                F\Checkbox::make('is_cif')
                    ->label('CIF Order?')
                    ->default(false)
                    ->afterStateUpdatedJs(<<<'JS'
                        $state == true ? $set('incoterm', 'CIF') : null;
                    JS),
            ])
                ->columns()
                ->columnSpanFull(),

            // auto calculate:
            // 'import_extra_costs', 
            // 'export_extra_costs', 

            // 'shipping_address',
            // 'billing_address',

            __notes()
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    public static function projectProducts(): array
    {
        return [
            F\Repeater::make('projectItems')
                ->hiddenLabel()
                ->relationship()
                ->table(POProductForm::repeaterHeaders())
                ->schema(POProductForm::formSchema())
                ->compact()
                ->defaultItems(1)
                ->minItems(1),
        ];
    }
}
