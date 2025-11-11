<?php

namespace App\Filament\Resources\SalesOrders;

use App\Filament\Resources\SalesOrders\Pages\CreateSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\EditSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\ListSalesOrders;
use App\Filament\Resources\SalesOrders\Schemas\SalesOrderForm;
use App\Filament\Resources\SalesOrders\Tables\SalesOrdersTable;
use App\Models\SalesOrder;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'sales';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return SalesOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesOrders::route('/'),
            'create' => CreateSalesOrder::route('/create'),
            'edit' => EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
