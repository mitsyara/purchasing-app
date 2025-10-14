<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\CustomsDataResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Actions as A;

class ManageCustomsData extends ManageRecords
{
    protected static string $resource = CustomsDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CustomsDataCluster::aggregateAction(),
        ];
    }
}
