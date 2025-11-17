<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\BasePage as Page;
use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;

use Filament\Actions\Action;

class UserActivities extends Page implements HasActions, HasSchemas
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    use InteractsWithActions, InteractsWithSchemas;

    protected string $view = 'filament.clusters.settings.pages.user-activities';

    // protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 1;

    #[\Livewire\Attributes\Url]
    public string $activeTab = 'activities';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'system';
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('refreshTable')
                ->label(__('Refresh'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info')
                ->action(function () {
                    $this->dispatch('refresh-custom-table');
                    \Filament\Notifications\Notification::make()
                        ->title('Data refreshed!')
                        ->success()
                        ->send();
                }),
        ];
    }
}
