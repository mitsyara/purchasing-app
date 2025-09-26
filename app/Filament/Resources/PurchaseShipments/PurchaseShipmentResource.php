<?php

namespace App\Filament\Resources\PurchaseShipments;

use App\Filament\Resources\PurchaseShipments\Tables\PurchaseShipmentTable;
use App\Filament\Resources\PurchaseShipments\Pages\ManagePurchaseShipments;
use App\Filament\Resources\PurchaseShipments\Schemas\PurchaseShipmentForm;
use App\Filament\Resources\PurchaseShipments\Schemas\PurchaseShipmentInfolist;
use App\Models\PurchaseShipment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseShipmentResource extends Resource
{
    protected static ?string $model = PurchaseShipment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'purchasing';

    public static function form(Schema $schema): Schema
    {
        return PurchaseShipmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseShipmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseShipmentTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePurchaseShipments::route('/'),
        ];
    }
}
