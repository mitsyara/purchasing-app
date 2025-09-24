<?php

namespace App\Filament\Resources\PurchaseShipments\Schemas;

use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\PurchaseShipment;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Infolists\Components as I;

class PurchaseShipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([]);
    }
}
