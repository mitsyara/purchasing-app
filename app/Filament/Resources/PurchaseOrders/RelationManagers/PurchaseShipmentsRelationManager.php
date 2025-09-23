<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Resources\PurchaseShipments\Schemas\PurchaseShipmentForm;
use App\Filament\Resources\PurchaseShipments\Schemas\PurchaseShipmentInfolist;
use App\Filament\Resources\PurchaseShipments\Tables\PurchaseShipmentTable;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PurchaseShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseShipments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Shipments');
    }

    public function form(Schema $schema): Schema
    {
        return PurchaseShipmentForm::configure($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return PurchaseShipmentInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return PurchaseShipmentTable::configure($table)
            ->modelLabel(fn(): string => __('Shipment'))
            ->pluralModelLabel(static::title())
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->after(function () {
                        // Sync Purchase Order Info
                        $purchaseOrder = $this->getOwnerRecord();
                        // new SyncOrderLinesInfo($purchaseOrder);
                    }),
            ]);
    }
}
