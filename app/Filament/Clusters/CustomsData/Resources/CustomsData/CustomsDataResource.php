<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages\ManageCustomsData;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomsData;
use Filament\Resources\Resource;
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
            ->query(fn() => CustomsData::select(self::selectedColumns()))
            ->defaultSort('import_date', 'desc')
            // ->paginationMode(\Filament\Tables\Enums\PaginationMode::Simple)
            // ->extremePaginationLinks(true)
            ->deferLoading()

            ->columns([
                T\TextColumn::make('import_date')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Import Date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('importer')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Importer'))
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['style' => 'width: 280px; white-space: normal; word-break: break-word;'])
                    ->toggleable(),

                T\TextColumn::make('product')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Product'))
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['style' => 'width: 480px; white-space: normal; word-break: break-word;'])
                    ->toggleable(),

                T\TextColumn::make('qty')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Quantity'))
                    ->numeric()
                    ->suffix(fn($record): ?string => $record->unit ? ' ' . $record->unit : null)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('unit')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Unit'))
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('price')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Price'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('value')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exporter')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Exporter'))
                    ->sortable()
                    ->extraAttributes(['style' => 'width: 280px; white-space: normal; word-break: break-word;'])
                    ->toggleable(),

                T\TextColumn::make('export_country')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Export Country'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('incoterm')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Incoterm'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('hscode')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('HS Code'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('category.name')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Category'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ]),

            ], \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth(\Filament\Support\Enums\Width::FourExtraLarge)

            ->recordActions([
                //
            ])
            ->headerActions([
                A\ExportAction::make()
                    ->exporter(\App\Filament\Exports\CustomsDataExporter::class)
                    ->color('teal')->outlined()
                    ->icon(Heroicon::ArrowDownTray)
                    ->label(__('Download Data'))
                    ->columnMappingColumns(2)
                    ->maxRows(10000)
                    ->chunkSize(200)
                    ->tooltip(__('10k rows max'))
                    ->disabled(fn() => $table->getAllSelectableRecordsCount() > 10000),
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
