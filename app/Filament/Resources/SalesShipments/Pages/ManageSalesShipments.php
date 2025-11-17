<?php

namespace App\Filament\Resources\SalesShipments\Pages;

use App\Filament\Resources\SalesShipments\Helpers\SalesShipmentResourceHelper;
use App\Filament\Resources\SalesShipments\SalesShipmentResource;
use App\Models\SalesShipment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesShipments extends ManageRecords
{
    protected static string $resource = SalesShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()->slideOver()
                ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
                ->mutateDataUsing(fn(CreateAction $action, array $data) => app(SalesShipmentResourceHelper::class)->syncData($action, $data)),
        ];
    }
}
