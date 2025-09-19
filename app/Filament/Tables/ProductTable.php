<?php

namespace App\Filament\Tables;

use App\Filament\Clusters\Settings\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class ProductTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Product::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();
                if ($categoryId = $arguments['category_id'] ?? null) {
                    return $query->where('category_id', $categoryId);
                }                
                return $query;
            })
            ->columns([
                __index(),

                T\TextColumn::make('product_code')
                    ->label(__('Code'))
                    ->sortable()
                    ->searchable(),

                T\TextColumn::make('product_name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),

                T\TextColumn::make('mfg.contact_name')
                    ->label(__('Manufacturer'))
                    ->sortable()
                    ->searchable(),

                T\TextColumn::make('category.category_name')
                    ->label(__('Category'))
                    ->visibleOn(ProductResource::class)
                    ->sortable()
                    ->searchable(),

                T\TextColumn::make('packing.packing_name')
                    ->label(__('Packing'))
                    ->sortable()
                    ->searchable(),

                T\TextColumn::make('product_life_cycle')
                    ->label(__('Life Cycle'))
                    ->sortable(),

                T\TextColumn::make('productAssortments.assortment.assortment_code')
                    ->label(__('Assortment'))
                    ->visibleOn(ProductResource::class)
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
