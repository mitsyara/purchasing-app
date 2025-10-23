<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages\ManageCustomsData;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomsData;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Tables\Filters\QueryBuilder\Constraints as C;

class CustomsDataResource extends Resource
{
    protected static ?string $model = CustomsData::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $cluster = CustomsDataCluster::class;

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->paginationMode(\Filament\Tables\Enums\PaginationMode::Simple)
            // ->extremePaginationLinks(true)
            // ->deferLoading()
            ->defaultSort('id', 'desc')
            ->deferFilters()

            ->columns([
                T\TextColumn::make('import_date')
                    ->label(__('Import Date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('importer')
                    ->wrap()
                    ->label(__('Importer'))
                    ->searchable(isIndividual: true)
                    ->toggleable(),

                T\TextColumn::make('product')
                    ->wrap()
                    ->label(__('Product'))
                    ->searchable(isIndividual: true)
                    ->toggleable(),

                T\TextColumn::make('qty')
                    ->label(__('Quantity'))
                    ->numeric()
                    ->suffix(fn($record): ?string => $record->unit ? ' ' . $record->unit : [null])
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('unit')
                    ->label(__('Unit'))
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('price')
                    ->label(__('Price'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('value')
                    ->label(__('Total'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exporter')
                    ->wrap()
                    ->label(__('Exporter'))
                    ->searchable(isIndividual: true)
                    ->toggleable(),

                T\TextColumn::make('export_country')
                    ->label(__('Export Country'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('incoterm')
                    ->label(__('Incoterm'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('hscode')
                    ->label(__('HS Code'))
                    ->sortable()
                    ->toggleable(),
            ])

            ->filters([
                TF\Filter::make('customFilters')
                    ->schema([
                        F\Checkbox::make('is_vett'),
                        F\Checkbox::make('is_null')
                            ->label(__('No Category Assigned')),
                    ])
                    ->columns(['default' => 2])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['is_vett'],
                                fn(Builder $query): Builder
                                => $query
                                    ->whereAny(['importer', 'product'], 'like', '%thú y%'),
                            )
                            ->when(
                                $data['is_null'],
                                fn(Builder $query): Builder
                                => $query
                                    ->whereNull('customs_data_category_id')
                            );
                    }),

                TF\QueryBuilder::make()
                    ->constraints([
                        C\DateConstraint::make('import_date'),
                        C\TextConstraint::make('importer'),
                        C\TextConstraint::make('exporter'),
                        C\TextConstraint::make('product'),
                        C\RelationshipConstraint::make('category')
                            ->selectable(
                                C\RelationshipConstraint\Operators\IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->preload()
                                    ->searchable()
                                    ->multiple(),
                            ),
                        C\NumberConstraint::make('qty'),
                        C\NumberConstraint::make('price'),
                        C\TextConstraint::make('export_country'),
                    ]),

            ], \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth(\Filament\Support\Enums\Width::FourExtraLarge)

            ->headerActions([
                A\ImportAction::make()
                    ->modal()
                    ->label('Upload from CSV')
                    ->icon(Heroicon::ArrowUpTray)
                    ->importer(\App\Filament\Imports\CustomsDataImporter::class)
                    ->color('info')
                    ->maxRows(50000)
                    ->chunkSize(1000)
                    ->fileRules([
                        'mimes:csv,txt',
                        'max:10240',
                    ])
                    ->visible(fn(): bool => auth()->id() === 1)
                    ->requiresConfirmation(),

                A\ExportAction::make()
                    ->modal()
                    ->exporter(\App\Filament\Exports\CustomsDataExporter::class)
                    ->color(fn(A\Action $action) => $action->isDisabled() ? 'gray' : 'teal')->outlined()
                    ->icon(Heroicon::ArrowDownTray)
                    ->label(__('Download Data'))
                    ->columnMappingColumns(2)
                    ->maxRows(10000)
                    ->chunkSize(1000)
                    ->tooltip(__('10k rows max'))
                    ->disabled(fn(Table $table) => $table->getAllSelectableRecordsCount() > 10000),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomsData::route('/'),
        ];
    }

    // Helpers

    public static function selectedColumns(): array
    {
        return [
            'id',
            'import_date',
            'importer',
            'product',
            'qty',
            'unit',
            'price',
            'value',
            'export_country',
            'exporter',
            'incoterm',
            'hscode',
            'customs_data_category_id',
        ];
    }
}
