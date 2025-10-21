<?php

namespace App\Filament\Resources\InventoryTransactions;

use App\Filament\Resources\InventoryTransactions\Pages\ManageInventoryTransactions;
use App\Models\InventoryTransaction;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Infolists\Components as I;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Group::make([
                    I\TextEntry::make('product.product_code')->label('Product Code'),
                    I\TextEntry::make('product.product_full_name')->label('Product'),
                    I\TextEntry::make('qty'),
                    I\TextEntry::make('import_price')->money('vnd', true),
                    I\TextEntry::make('created_at'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('transaction_type')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('company.company_code')
                    ->label('Company')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('warehouse.warehouse_name')
                    ->label('Warehouse')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('product.product_full_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('transaction_date')
                    ->label('Import Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('lot_no')
                    ->label('Lot No')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('import_price')
                    ->label('Import Price')
                    ->money('vnd', true)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('mfg_date')
                    ->label('Mfg Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exp_date')
                    ->label('Exp Date')
                    ->date('d/m/Y')
                    ->color(fn($state): ?string => $state < today() ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // A\ViewAction::make(),
            ])
            ->toolbarActions([
                // A\BulkActionGroup::make([
                // ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryTransactions::route('/'),
        ];
    }
}
