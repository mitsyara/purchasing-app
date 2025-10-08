<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages;

use App\Filament\Clusters\CustomsData\Resources\CustomsData\CustomsDataResource;
use Filament\Actions as A;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageCustomsData extends ManageRecords
{
    protected static string $resource = CustomsDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            A\ImportAction::make()
                ->label('Upload from CSV')
                ->icon(Heroicon::ArrowUpTray)
                ->importer(\App\Filament\Imports\CustomsDataImporter::class)
                ->color('info')
                ->maxRows(100000)
                ->chunkSize(200)
                ->fileRules([
                    'mimes:csv',
                    'max:10240',
                ])
                ->requiresConfirmation(),
        ];
    }
}
