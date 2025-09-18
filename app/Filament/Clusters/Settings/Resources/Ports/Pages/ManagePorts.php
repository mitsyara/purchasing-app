<?php

namespace App\Filament\Clusters\Settings\Resources\Ports\Pages;

use App\Filament\Clusters\Settings\Resources\Ports\PortResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePorts extends ManageRecords
{
    protected static string $resource = PortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
