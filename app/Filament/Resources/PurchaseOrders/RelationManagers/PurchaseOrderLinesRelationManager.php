<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Schemas\POProductForm;
use App\Services\PurchaseOrder\PurchaseOrderService;
use Filament\Resources\RelationManagers\RelationManager;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Actions as A;
use Filament\Forms\Components as F;

class PurchaseOrderLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrderLines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Products');
    }

    public function form(Schema $schema): Schema
    {
        return POProductForm::configure($schema)
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(fn(): string => __('Product'))
            ->pluralModelLabel(static::title())
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => $query
                    ->with(['product', 'assortment'])
                    ->leftJoin('products', 'purchase_order_lines.product_id', '=', 'products.id')
                    ->leftJoin('assortments', 'purchase_order_lines.assortment_id', '=', 'assortments.id')
                    ->selectRaw('purchase_order_lines.*, COALESCE(products.product_description, assortments.assortment_name) as combined_product')
            )
            ->columns([
                __index(),

                T\TextColumn::make('combined_product')
                    ->label(__('Product'))
                    ->color(fn($record) => $record->assortment_id ? 'danger' : null)
                    ->sortable(),

                T\TextColumn::make('qty')
                    ->numeric()
                    ->sortable(),

                T\TextColumn::make('unit_price')
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

                T\TextColumn::make('display_contract_price')
                    ->label(__('Contract price'))
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
                    ->after(function (): void {
                        // Sync Purchase Order Info
                        $purchaseOrder = $this->getOwnerRecord();
                        app(PurchaseOrderService::class)->syncOrderLinesInfo($purchaseOrder->id);
                        app(PurchaseOrderService::class)->updateOrderInfo($purchaseOrder->id);
                    }),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(function (): void {
                        // Sync Purchase Order Info
                        $purchaseOrder = $this->getOwnerRecord();
                        app(PurchaseOrderService::class)->syncOrderLinesInfo($purchaseOrder->id);
                        app(PurchaseOrderService::class)->updateOrderInfo($purchaseOrder->id);
                    }),
                A\DeleteAction::make()
                    ->after(function (): void {
                        // Sync Purchase Order Info
                        $purchaseOrder = $this->getOwnerRecord();
                        app(PurchaseOrderService::class)->updateOrderInfo($purchaseOrder->id);
                    }),
            ]);
    }
}
