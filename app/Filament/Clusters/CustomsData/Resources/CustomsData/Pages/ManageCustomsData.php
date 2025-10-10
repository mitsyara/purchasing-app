<?php

namespace App\Filament\Clusters\CustomsData\Resources\CustomsData\Pages;

use App\Filament\Clusters\CustomsData\CustomsDataCluster;
use App\Filament\Clusters\CustomsData\Resources\CustomsData\CustomsDataResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Actions as A;
use Filament\Support\Enums\Width;

class ManageCustomsData extends ManageRecords
{
    protected static string $resource = CustomsDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            A\ActionGroup::make([
                A\ActionGroup::make([
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

                    CustomsDataCluster::aggregateAction(),
                ])
                    ->dropdown(false),

                A\Action::make('manageCategories')
                    ->label(__('Manage Categories'))
                    ->color('secondary')
                    ->modal()->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->schema([])

            ])
                ->label(__('Actions'))
                ->color('teal')
                ->button(),
        ];
    }
}
