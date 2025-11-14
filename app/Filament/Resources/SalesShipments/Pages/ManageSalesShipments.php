<?php

namespace App\Filament\Resources\SalesShipments\Pages;

use App\Filament\Resources\SalesShipments\SalesShipmentResource;
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
                ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
                ->mutateDataUsing(function (CreateAction $action, array $data) {
                    dd($data);
                }),
        ];
    }
}
