<?php

namespace App\Filament\Resources\SalesOrders\RelationManagers;

use App\Filament\Schemas\POProductForm;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Actions as A;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SalesOrderLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'salesOrderLines';

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
        $currency = $this->getOwnerRecord()->currency ?? 'VND';

        return $table
            ->modelLabel(fn(): string => __('Product'))
            ->pluralModelLabel(static::title())
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => $query
                    ->with(['product', 'assortment'])
                    ->leftJoin('products', 'sales_order_lines.product_id', '=', 'products.id')
                    ->leftJoin('assortments', 'sales_order_lines.assortment_id', '=', 'assortments.id')
                    ->selectRaw('sales_order_lines.*, COALESCE(products.product_description, assortments.assortment_name) as combined_product')
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
                    ->money($currency)
                    ->sortable(),

                T\TextColumn::make('display_contract_price')
                    ->label(__('Contract price'))
                    ->money($currency)
                    ->sortable(),

                T\TextColumn::make('value')
                    ->money($currency)
                    ->sortable(),
                T\TextColumn::make('contract_value')
                    ->money($currency)
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->after(fn() => $this->syncAfterSave()),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(fn() => $this->syncAfterSave()),
                A\DeleteAction::make()
                    ->after(fn() => $this->syncAfterSave()),
            ]);
    }

    public function syncAfterSave(): void
    {
        $salesOrder = $this->getOwnerRecord();
        app(\App\Services\SalesOrder\SalesOrderService::class)->syncOrderInfo($salesOrder->id);
    }
}
