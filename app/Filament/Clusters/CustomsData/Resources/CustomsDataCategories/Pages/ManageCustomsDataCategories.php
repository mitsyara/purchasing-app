<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsDataCategories\Pages;

use App\Filament\Clusters\CustomsData\Resources\CustomsDataCategories\CustomsDataCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomsDataCategories extends ManageRecords
{
    protected static string $resource = CustomsDataCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(\Filament\Support\Enums\Width::Large),
        ];
    }
}
