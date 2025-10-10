<?php

namespace App\Filament\Clusters\CustomsData\Pages;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\CustomsDataResource;
use App\Models\CustomsData;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

use Filament\Forms\Components as F;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Tables\Table;

class CustomsDataReportByCategory extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.clusters.customs-data.pages.customs-data-report-by-category';

    protected static ?string $cluster = CustomsDataCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Summary by Category');
    }

    protected function getTableQuery(): Builder
    {
        return CustomsData::query()
            ->select('importer')
            ->selectRaw('COUNT(product) as total_import')
            ->selectRaw('SUM(qty) as total_qty')
            ->selectRaw('SUM(value) as total_value')
            ->groupBy(['importer', 'customs_data.id']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('total_value', 'desc')
            ->deferLoading()
            ->columns([
                T\TextColumn::make('index')->label('#')
                    ->rowIndex(),

                T\TextColumn::make('importer')
                    ->sortable(),

                T\TextColumn::make('category.name')
                    ->default('Khác')
                    ->sortable(),

                T\TextColumn::make('total_import')->label('Count')
                    ->numeric()
                    ->summarize(T\Summarizers\Sum::make())
                    ->sortable(),

                T\TextColumn::make('total_qty')->label('Total Qty')
                    ->numeric()
                    ->summarize(T\Summarizers\Sum::make())
                    ->sortable(),

                T\TextColumn::make('total_value')->label('Total Value')
                    ->money('USD')
                    ->summarize(T\Summarizers\Sum::make())
                    ->sortable(),

            ])
            ->filters([
                TF\Filter::make('customFilters')
                    ->default()
                    ->schema([
                        F\Checkbox::make('is_vett'),
                        F\TextInput::make('importer'),
                        F\Select::make('customs_data_category_id')->label(__('Category'))
                            ->options(fn() => Cache::rememberForever('customs_data_categories.all', function (): Collection {
                                return \App\Models\CustomsDataCategory::all(['id', 'name', 'keywords']);
                            })->pluck('name', 'id'))
                            ->preload()
                            ->searchable()
                            ->multiple(),
                        F\DatePicker::make('from_date')
                            ->default(today()->addMonths(-3))
                            ->maxDate(today()),
                        F\DatePicker::make('to_date')
                            ->default(today())
                            ->maxDate(today()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['is_vett'],
                                fn($q) =>
                                $q->whereAny(['importer', 'product'], 'like', '%thú y%')
                            )
                            ->when(
                                $data['importer'],
                                fn($q, $importer) =>
                                $q->where('importer', 'like', "%$importer%")
                            )
                            ->when(
                                $data['customs_data_category_id'],
                                fn($q, $ids) =>
                                $q->whereIn('customs_data_category_id', $ids)
                            )
                            ->when(
                                $data['from_date'],
                                fn($q) =>
                                $q->whereDate('import_date', '>=', $data['from_date'])
                            )
                            ->when(
                                $data['to_date'],
                                fn($q) =>
                                $q->whereDate('import_date', '<=', $data['to_date'])
                            );
                    })
            ]);
    }

    public function getTableRecordKey(Model|array $record): string
    {
        return uniqid();
    }
}
