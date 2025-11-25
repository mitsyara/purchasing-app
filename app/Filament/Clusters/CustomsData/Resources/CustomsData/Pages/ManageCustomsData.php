<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages;

use App\Filament\Clusters\CustomsData\Resources\CustomsData\CustomsDataResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomsData extends ManageRecords
{
    protected static string $resource = CustomsDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
