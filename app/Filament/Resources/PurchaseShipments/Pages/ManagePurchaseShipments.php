<?php

namespace App\Filament\Resources\PurchaseShipments\Pages;

use App\Filament\Resources\PurchaseShipments\PurchaseShipmentResource;
use App\Models\PurchaseShipment;
use App\Services\PurchaseShipment\CallAllServices;
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
                ->modalWidth(Width::SevenExtraLarge)
                ->after(function (PurchaseShipment $record) {
                    new CallAllServices($record);
                })
                ->visible(false),
        ];
    }
}
