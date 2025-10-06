<?php

namespace App\Filament\Clusters\Settings\Pages;

use Filament\Pages\Page;
use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserActivities extends Page
{

    protected string $view = 'filament.clusters.settings.pages.user-activities';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 40;

    public string $activePanel = 'activities';

    public static function canAccess(): bool
    {
        return auth()->id() === 1;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('Application');
    }
}
