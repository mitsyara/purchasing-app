<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\BasePage as Page;
use Filament\Support\Icons\Heroicon;

class MyProfile extends Page
{
    protected string $view = 'filament.clusters.settings.pages.my-profile';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('User Settings');
    }
}
