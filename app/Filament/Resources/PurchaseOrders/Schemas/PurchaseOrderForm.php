<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use FFI;
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

                F\Select::make('company_id')
                    ->label(__('Company'))
                    ->relationship(
                        name: 'company',
                        titleAttribute: 'company_name',
                    )
                    ->required(),

                F\Select::make('supplier_id')
                    ->label(__('Supplier'))
                    ->relationship(
                        name: 'supplier',
                        titleAttribute: 'contact_name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true),
                    )
                    ->disableOptionWhen(fn($get, $value) => (int) $get('3rd_party_id') === (int) $value)
                    ->preload()
                    ->searchable()
                    ->required(),

                F\Select::make('3rd_party_id')
                    ->label(__('3rd Party'))
                    ->relationship(
                        name: 'thirdParty',
                        titleAttribute: 'contact_name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query->where('is_trader', true),
                    )
                    ->disableOptionWhen(fn($get, $value) => (int) $get('supplier_id') === (int) $value)
                    ->preload()
                    ->searchable(),

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

                F\DatePicker::make('etd_min')
                    ->label(__('From (ETD)'))
                    ->minDate(fn($get) => $get('order_date'))
                    ->maxDate(fn($get) => $get('etd_max')),

                F\DatePicker::make('etd_max')
                    ->label(__('To (ETD)'))
                    ->minDate(fn($get) => $get('etd_min') ?? $get('order_date'))
                    ->maxDate(today()->addMonths(2)),

                F\DatePicker::make('eta_min')
                    ->label(__('From (ETA)'))
                    ->minDate(fn($get) => $get('etd_max') ?? $get('etd_min') ?? $get('order_date'))
                    ->maxDate(fn($get) => $get('eta_max')),

                F\DatePicker::make('eta_max')
                    ->label(__('To (ETA)'))
                    ->minDate(fn($get) => $get('eta_min') ?? $get('etd_max') ?? $get('etd_min') ?? $get('order_date')),

            ]);
    }

    public static function generalFields(): array
    {
        return [
            F\Select::make('order_status')
                ->label(__('Order Status'))
                ->options(\App\Enums\OrderStatusEnum::class)
                ->default(\App\Enums\OrderStatusEnum::Draft)
                ->required(),

            F\TextInput::make('order_number')
                ->label(__('Order Number'))
                ->unique(),

            F\DatePicker::make('order_date')
                ->label(__('Order Date'))
                ->minDate(today()->subMonths(6))
                ->maxDate(today()),

        ];
    }
}
