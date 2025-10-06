<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsDataCategories;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\CustomsDataResource;
use App\Filament\Clusters\CustomsData\Resources\CustomsDataCategories\Pages\ManageCustomsDataCategories;
use App\Models\CustomsDataCategory;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class CustomsDataCategoryResource extends Resource
{
    protected static ?string $model = CustomsDataCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = CustomsDataCluster::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return CustomsDataResource::getNavigationLabel();
    }

    public static function getModelLabel(): string
    {
        return 'Categories';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                F\TagsInput::make('keywords')
                    ->label('Keywords')
                    ->helperText('Add keywords to help with searching. Separate multiple keywords with commas.')
                    ->separator(',')
                    ->columnSpanFull(),

                F\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                __index(),

                T\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('keywords')
                    ->label('Keywords')
                    ->separator()
                    ->badge()
                    ->toggleable(),

                T\TextColumn::make('customs_datas_count')
                    ->counts('customsDatas')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->modalWidth(\Filament\Support\Enums\Width::Large),
                    A\DeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomsDataCategories::route('/'),
        ];
    }
}
