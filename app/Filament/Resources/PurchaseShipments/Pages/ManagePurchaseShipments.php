<?php

namespace App\Filament\Resources\PurchaseShipments\Pages;

use App\Filament\Resources\PurchaseShipments\PurchaseShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManagePurchaseShipments extends ManageRecords
{
    protected static string $resource = PurchaseShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()->slideOver()
                ->modalWidth(Width::SevenExtraLarge),
        ];
    }
}
