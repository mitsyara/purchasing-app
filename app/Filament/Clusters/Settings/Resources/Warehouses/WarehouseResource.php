<?php

namespace App\Filament\Clusters\Settings\Resources\Warehouses;

use App\Filament\Clusters\Settings\Resources\Warehouses\Pages\ManageWarehouses;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?int $navigationSort = 12;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('Company Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\ToggleButtons::make('region')
                    ->label(__('Region'))
                    ->options(\App\Enums\RegionEnum::class)
                    ->grouped()
                    ->required()
                    ->columnSpanFull(),

                F\TextInput::make('warehouse_code')
                    ->label(__('Code'))
                    ->maxLength(255),

                F\TextInput::make('warehouse_name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),

                F\TextInput::make('warehouse_address')
                    ->label(__('Address'))
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),
                T\TextColumn::make('warehouse_code')
                    ->label(__('Code'))
                    ->sortable()
                    ->searchable(),
                T\TextColumn::make('warehouse_name')    
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                T\TextColumn::make('warehouse_address')
                    ->label(__('Address'))
                    ->sortable()
                    ->searchable(),
                T\TextColumn::make('region')
                    ->label(__('Region'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWarehouses::route('/'),
        ];
    }
}
