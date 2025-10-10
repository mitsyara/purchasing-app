<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporters;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporters\Pages\ManageCustomsDataByImporters;
use App\Models\CustomsDataByImporter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Database\Eloquent\Builder;

class CustomsDataByImporterResource extends Resource
{
    protected static ?string $model = CustomsDataByImporter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $cluster = CustomsDataCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('By Importer');
    }
    public static function getModelLabel(): string
    {
        return static::getNavigationLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
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
                    ->color(fn($record) => $record->is_vett ? 'success' : null)
                    ->wrap(),

                T\TextColumn::make('total_import')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total Import'))
                    ->summarize(T\Summarizers\Sum::make())
                    ->sortable()
                    ->numeric(),

                T\TextColumn::make('total_qty')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total Qty'))
                    ->summarize(T\Summarizers\Sum::make())
                    ->sortable()
                    ->numeric(),

                T\TextColumn::make('total_value')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total Value'))
                    ->summarize(T\Summarizers\Sum::make())
                    ->sortable()
                    ->money('USD'),

                T\TextColumn::make('import_months')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Import Date'))
                    ->sortable(),
            ])
            ->filters([
                TF\Filter::make('customFilters')
                    ->schema([
                        F\Select::make('is_vett')
                            ->label(__('Is VETT'))
                            ->options([
                                false => __('Other'),
                                true => __('Veterinary'),
                            ])
                            ->nullable(),

                        F\DatePicker::make('from_date')
                            ->maxDate(today())
                            ->format('Y-m'),

                        F\DatePicker::make('to_date')
                            ->maxDate(today())
                            ->format('Y-m'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['is_vett'], fn(Builder $sq, $is_vett) => $sq->where('is_vett', $is_vett));
                    })
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomsDataByImporters::route('/'),
        ];
    }

    // month/year selection array
    public static function getMonthYearSelection(): array
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = 'Tháng ' . str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        // 2. Mảng năm (từ 2024 đến năm hiện tại)ta
        $currentYear = (int) date('Y');
        $years = [];
        for ($y = 2024; $y <= $currentYear; $y++) {
            $years[$y] = $y;
        }

        return [
            'months' => $months,
            'years' => $years,
        ];
    }
}
