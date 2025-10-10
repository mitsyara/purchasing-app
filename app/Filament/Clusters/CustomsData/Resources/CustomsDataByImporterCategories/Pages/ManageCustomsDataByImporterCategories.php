<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporterCategories\Pages;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsDataByImporterCategories\CustomsDataByImporterCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomsDataByImporterCategories extends ManageRecords
{
    protected static string $resource = CustomsDataByImporterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CustomsDataCluster::aggregateAction(),
        ];
    }
}
