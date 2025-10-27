<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Models\PurchaseOrder;
use App\Filament\BaseResource as Resource;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'purchasing';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'order_number';
    public static function getRecordTitle(?Model $record): string | Htmlable | null
    {
        return $record?->order_number ?? __('Draft');
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\PurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\PurchaseOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchaseOrderLinesRelationManager::class,
            RelationManagers\PurchaseShipmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
