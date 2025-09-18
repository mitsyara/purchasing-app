<?php

namespace App\Filament\Clusters\Settings\Resources\Assortments\Pages;

use App\Filament\Clusters\Settings\Resources\Assortments\AssortmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssortments extends ManageRecords
{
    protected static string $resource = AssortmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
