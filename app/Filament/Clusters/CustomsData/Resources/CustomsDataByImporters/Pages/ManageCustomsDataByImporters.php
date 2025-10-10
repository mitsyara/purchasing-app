<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporters\Pages;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporters\CustomsDataByImporterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomsDataByImporters extends ManageRecords
{
    protected static string $resource = CustomsDataByImporterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CustomsDataCluster::aggregateAction(),
        ];
    }
}
