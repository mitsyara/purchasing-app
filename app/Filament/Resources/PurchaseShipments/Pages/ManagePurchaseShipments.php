<?php

namespace App\Filament\Resources\PurchaseShipments\Pages;

use App\Filament\Resources\PurchaseShipments\PurchaseShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePurchaseShipments extends ManageRecords
{
    protected static string $resource = PurchaseShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TODO: Implement header actions when needed
        ];
    }
}
