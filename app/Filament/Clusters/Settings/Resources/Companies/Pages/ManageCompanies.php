<?php

namespace App\Filament\Clusters\Settings\Resources\Companies\Pages;

use App\Filament\Clusters\Settings\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver(),
        ];
    }
}
