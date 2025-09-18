<?php

namespace App\Filament\Clusters\Settings\Resources\Warehouses\Pages;

use App\Filament\Clusters\Settings\Resources\Warehouses\WarehouseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouses extends ManageRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
