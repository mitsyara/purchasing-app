<?php

namespace App\Filament\Clusters\Project;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class ProjectCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|\UnitEnum|null $navigationGroup = 'purchasing';

    protected static ?int $navigationSort = 12;

    protected static bool $shouldRegisterNavigation = false;

}
