<?php

namespace App\Filament\Resources\SalesDeliveryScheduleLines\Pages;

use App\Filament\Resources\SalesDeliveryScheduleLines\SalesDeliveryScheduleLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesDeliveryScheduleLines extends ManageRecords
{
    protected static string $resource = SalesDeliveryScheduleLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
