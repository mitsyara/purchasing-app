<?php

namespace App\Filament\Clusters\CustomsData\Pages;

use App\Models\CustomsData;
use App\Models\CustomsDataSummary as CustomsDataSummaryModel;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\BasePage as Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Actions as A;

class CustomsDataSummary extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.clusters.customs-data.pages.customs-data-summary';

    protected static ?string $cluster = CustomsDataCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?int $navigationSort = 2;

    // protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Summary');
    }

    protected function getTableQuery(): Builder
    {
        return CustomsDataSummaryModel::query()
            ->with('category:id,name')
            // Use optimized scope
            ->forSummaryTable();
    }

    public function table(Table $table): Table
    {
        $maxImportDate = \Carbon\Carbon::parse(CustomsDataSummaryModel::max('import_date')) ?? today();

        return $table
            ->defaultSort('total_value', 'desc')
            ->defaultKeySort(false)
            ->deferLoading()
            ->query(fn() => $this->getTableQuery())
            ->paginated([50, 100])
            ->defaultPaginationPageOption(50)

            // Group columns
            ->groups([
                \Filament\Tables\Grouping\Group::make('importer')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('category.name')
                    ->collapsible(),
            ])
            ->collapsedGroupsByDefault()

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
                        S\Flex::make([
                            F\TextInput::make('importer'),

                            F\Select::make('customs_data_category_id')->label(__('Category'))
                                ->options(fn() => Cache::rememberForever('customs_data_categories.all', function (): Collection {
                                    return \App\Models\CustomsDataCategory::all(['id', 'name', 'keywords']);
                                })->pluck('name', 'id'))
                                ->preload()
                                ->searchable()
                                ->multiple()
                                ->disabled(fn(callable $get) => $get('null_category') ?? false),

                            F\DatePicker::make('from_date')
                                ->maxDate(today())
                                // Default 1 quarter
                                ->default(fn() => $maxImportDate->copy()->subMonths(4))
                                ->grow(false),
                            F\DatePicker::make('to_date')
                                ->maxDate(today())
                                ->default(fn() => $maxImportDate)
                                ->grow(false),
                        ])
                            ->from('md'),

                        S\Flex::make([
                            F\Checkbox::make('is_vett')
                                ->grow(false),
                            F\Checkbox::make('null_category')
                                ->label(__('No Category'))
                                ->afterStateUpdated(fn(callable $set, $state) 
                                => $state ? $set('customs_data_category_id', null) : null)
                                ->live()
                                ->partiallyRenderComponentsAfterStateUpdated(['customs_data_category_id'])
                                ->grow(false),
                        ])
                            ->from('sm')
                    ])
                    ->query(function (Builder $query, array $data) {
                        // Use optimized scope
                        return $query->optimizedForFilters($data);
                    })
                    ->columnSpanFull(),
            ], \Filament\Tables\Enums\FiltersLayout::AboveContent)
        ;
    }

    public function getRecordKey(Model|array $record): string
    {
        return $this->getTableRecordKey($record);
    }

    public function getTableRecordKey(Model|array $record): string
    {
        return uniqid();
    }

    /**
     * Old query from raw customs data table for reference
     */
    protected function oldQuery(): Builder
    {
        return CustomsData::query()
            ->with('category:id,name')
            ->when($this->fromDate, fn($q) => $q->whereDate('import_date', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('import_date', '<=', $this->toDate))
            ->select('importer', 'customs_data_category_id')
            ->selectRaw('COUNT(product) as total_import')
            ->selectRaw('SUM(qty) as total_qty')
            ->selectRaw('SUM(value) as total_value')
            ->groupBy('importer', 'customs_data_category_id');
    }
}
