<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporterCategories;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporterCategories\Pages\ManageCustomsDataByImporterCategories;
use App\Models\CustomsDataByImporterCategory;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Forms\Components as F;
use Filament\Actions as A;

class CustomsDataByImporterCategoryResource extends Resource
{
    protected static ?string $model = CustomsDataByImporterCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $cluster = CustomsDataCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('By Category');
    }
    public static function getModelLabel(): string
    {
        return static::getNavigationLabel();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('total_value', 'desc')
            ->columns([
                __index(),

                T\TextColumn::make('importer')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Importer'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                T\TextColumn::make('category.name')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Category'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('total_import')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total Import'))
                    ->sortable()
                    ->summarize(T\Summarizers\Sum::make())
                    ->numeric(),

                T\TextColumn::make('total_qty')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total Qty'))
                    ->sortable()
                    ->summarize(T\Summarizers\Sum::make())
                    ->numeric(),

                T\TextColumn::make('total_value')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total Value'))
                    ->sortable()
                    ->summarize(T\Summarizers\Sum::make())
                    ->money('USD'),

            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomsDataByImporterCategories::route('/'),
        ];
    }
}
