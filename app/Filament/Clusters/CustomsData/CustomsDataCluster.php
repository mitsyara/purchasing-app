<?php

namespace App\Filament\Clusters\CustomsData;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class CustomsDataCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

}
