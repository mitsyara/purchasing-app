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
            ->query(fn(): Builder => Product::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();
                if ($categoryId = $arguments['belong_category_id'] ?? null) {
                    return $query->where('category_id', $categoryId);
                }
                return $query
                    ->when($arguments['belong_category_id'] ?? null, fn(Builder $query, $categoryId) => $query
                        ->where('category_id', $categoryId))
                    ->when($arguments['category_id'] ?? null, fn(Builder $query, $categoryId) => $query
                        ->where('category_id', $categoryId)
                        ->orWhereNull('category_id'))
                ;
            })
            ->columns(static::tableColumns())
            ->filters([
                TF\SelectFilter::make('category_id')
                    ->label(__('Category'))
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'category_name',
                        modifyQueryUsing: fn($query): Builder => $query
                    ),
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

    public static function tableColumns(): array
    {
        return [
            __index()
                ->size('xs'),

            T\TextColumn::make('product_code')
                ->label(__('Code'))
                ->size('xs')
                ->sortable()
                ->searchable(),

            T\TextColumn::make('product_name')
                ->label(__('Name'))
                ->size('xs')
                ->sortable()
                ->searchable(),

            T\TextColumn::make('mfg.contact_name')
                ->label(__('Manufacturer'))
                ->size('xs')
                ->sortable()
                ->searchable(),

            T\TextColumn::make('category.category_name')
                ->label(__('Category'))
                ->size('xs')
                // ->visibleOn(ProductResource::class)
                ->sortable()
                ->searchable(),

            T\TextColumn::make('packing.packing_name')
                ->label(__('Packing'))
                ->size('xs')
                ->sortable()
                ->searchable(),

            T\TextColumn::make('product_life_cycle')
                ->label(__('Life Cycle'))
                ->size('xs')
                ->sortable(),

            T\TextColumn::make('productAssortments.assortment.assortment_code')
                ->label(__('Assortment'))
                ->size('xs')
                ->visibleOn(ProductResource::class)
                ->sortable()
                ->searchable(),
        ];
    }
}
