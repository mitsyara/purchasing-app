<?php

namespace App\Filament\Clusters\CustomsData;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class CustomsDataCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|\UnitEnum|null $navigationGroup = 'other';

    protected static ?int $navigationSort = 91;


    public static function aggregateAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('aggregateData')
            ->label('Sync Reports')
            ->icon(\Filament\Support\Icons\Heroicon::ChartBarSquare)
            ->color('success')
            ->action(function () {
                \App\Jobs\RecalculateCustomsDataByImporterJob::dispatch();
                \App\Jobs\RecalculateCustomsDataByImporterCategoryJob::dispatch();

                \Filament\Notifications\Notification::make()
                    ->title('Data Sync Started!')
                    ->body('This may take a while. You will be notified when complete.')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->visible(fn() => auth()->id() === 1);
    }
}
