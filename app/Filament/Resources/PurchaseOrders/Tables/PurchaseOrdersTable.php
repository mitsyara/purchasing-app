<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Models\PurchaseOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class PurchaseOrdersTable
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
                    ->description(fn(PurchaseOrder $record) => $record->order_number)
                    ->sortable()
                    ->searchable(query: fn(Builder $query, string $search): Builder => $query->orWhere('order_number', 'like', "%{$search}%"))
                    ->toggleable(),

                T\TextColumn::make('company.company_code')
                    ->label(__('Company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('supplier.contact_name')
                    ->label(__('Supplier'))
                    ->description(fn($record) => $record->thirdParty?->contact_name)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('staffBuy.name')
                    ->label(__('Purchasing Staff'))
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
