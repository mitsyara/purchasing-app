<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages\ManageCustomsData;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class CustomsDataResource extends Resource
{
    protected static ?string $model = CustomsData::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = CustomsDataCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Group::make([
                    F\Select::make('customs_data_category_id')
                        ->relationship(
                            name: 'category',
                            titleAttribute: 'name',
                        )
                        // ->createOptionForm(CustomsDataCategoryResource::createForm())
                        // ->editOptionForm(CustomsDataCategoryResource::createForm())
                        ->preload()
                        ->searchable(),

                    F\DatePicker::make('import_date')
                        ->format('d/m/Y')
                        ->required(),

                    F\TextInput::make('importer')
                        ->columnSpanFull()
                        ->required(),

                    F\TextInput::make('exporter')
                        ->columnSpanFull(),

                    F\TextInput::make('product')
                        ->columnSpanFull()
                        ->required(),

                    S\Group::make([
                        F\TextInput::make('qty')
                            ->numeric()
                            ->minValue(0.001)
                            ->required(),
                        F\TextInput::make('unit'),

                        F\TextInput::make('price')
                            ->numeric()
                            ->suffix('USD')
                            ->minValue(0)
                            ->required(),
                    ])
                        ->columns(3)
                        ->columnSpanFull(),

                    S\Group::make([
                        F\TextInput::make('export_country'),
                        F\Select::make('incoterm')
                            ->options(\App\Enums\IncotermEnum::class),
                        F\TextInput::make('hscode'),
                    ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                    ->columns()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn() => CustomsData::select(self::selectedColumns()))
            ->defaultSort('import_date', 'desc')
            ->paginationMode(\Filament\Tables\Enums\PaginationMode::Simple)
            ->extremePaginationLinks()
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
                    ->toggleable(),

                T\TextColumn::make('product')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Product'))
                    ->searchable()
                    ->sortable()
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

                T\TextColumn::make('total')
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
                TF\Filter::make('custom_filters')
                    ->schema([
                        F\Select::make('category')
                            ->options(fn() => Cache::get('customs_data_categories.all')
                                ->pluck('name', 'id'))
                            ->multiple(),
                    ])
            ])
            ->recordActions([
                //     A\ActionGroup::make([
                //         A\EditAction::make()
                //             ->modal()->slideOver(),
                //         A\DeleteAction::make(),
                //     ]),
            ])
            ->headerActions([
                A\ExportAction::make()
                    ->exporter(\App\Filament\Exports\CustomsDataExporter::class)
                    ->color('teal')
                    ->icon(Heroicon::ArrowDownTray)
                    ->label(__('Export to CSV'))
                    ->columnMappingColumns(2)
                    ->maxRows(10000)
                    ->chunkSize(200),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\CustomsDataExporter::class)
                        ->icon(Heroicon::ArrowDownTray)
                        ->label(__('Export selected to CSV'))
                        ->columnMappingColumns(2)
                        ->maxRows(1000)
                        ->chunkSize(200),
                    A\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->id === 1),
                ]),
            ]);
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
            'total',
            'export_country',
            'exporter',
            'incoterm',
            'hscode',
            'customs_data_category_id',
        ];
    }
}
