<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Schemas\POProductForm;
use App\Services\PurchaseOrder\SyncOrderLineInfo;
use Filament\Resources\RelationManagers\RelationManager;


use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Actions as A;
use Filament\Forms\Components as F;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrderLines';

    public function form(Schema $schema): Schema
    {
        return POProductForm::configure($schema)
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => $query
                    ->with(['product', 'assortment'])
                    ->leftJoin('products', 'purchase_order_lines.product_id', '=', 'products.id')
                    ->leftJoin('assortments', 'purchase_order_lines.assortment_id', '=', 'assortments.id')
                    ->selectRaw('purchase_order_lines.*, COALESCE(products.product_full_name, assortments.assortment_name) as combined_product')
            )
            ->columns([
                __index(),

                T\TextColumn::make('combined_product')
                    ->label(__('Product'))
                    ->sortable(),

                T\TextColumn::make('qty')
                    ->numeric()
                    ->sortable(),

                T\TextColumn::make('unit_price')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

                T\TextColumn::make('contract_price')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

                T\TextColumn::make('value')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),
                T\TextColumn::make('contract_value')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->label(__('Add Product'))
                    ->after(function () {
                        // Sync Purchase Order Info
                        $purchaseOrder = $this->getOwnerRecord();
                        new SyncOrderLineInfo($purchaseOrder);
                    }),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(function () {
                        // Sync Purchase Order Info
                        $purchaseOrder = $this->getOwnerRecord();
                        new SyncOrderLineInfo($purchaseOrder);
                    }),
                A\DeleteAction::make(),
            ]);
    }
}
