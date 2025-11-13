<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use App\Models\SalesOrder;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Illuminate\Database\Eloquent\Builder;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('order_status')
                    ->label(__('Status'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('order_date')
                    ->date('d/m/Y')
                    ->label(__('Order Date'))
                    ->description(fn(SalesOrder $record) => $record->order_number)
                    ->sortable()
                    ->searchable(query: fn(Builder $query, string $search): Builder
                    => $query->orWhere('order_number', 'like', "%{$search}%"))
                    ->toggleable(),

                T\TextColumn::make('company.company_code')
                    ->label(__('Company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('customer.contact_name')
                    ->label(__('Customer'))
                    ->description(fn(SalesOrder $record) => $record->customerContract?->contact_name)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('staffSales.name')
                    ->label(__('Sales Staff'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('total_value')
                    ->label(__('Value'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('total_contract_value')
                    ->label(__('Contract Value'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('total_received_value')
                    ->label(__('Received'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('total_paid_value')
                    ->label(__('Paid'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make(),
                    A\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
