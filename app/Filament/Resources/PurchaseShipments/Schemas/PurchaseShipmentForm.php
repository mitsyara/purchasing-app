<?php

namespace App\Filament\Resources\PurchaseShipments\Schemas;
use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Illuminate\Database\Eloquent\Builder;

class PurchaseShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                S\Tabs::make('Shipment')
                    ->tabs([
                        S\Tabs\Tab::make(__('Shipment Info'))
                            ->schema([
                                ...static::shipmentInfoFields(),
                            ])
                    ])
            ]);
    }

    public static function shipmentInfoFields(): array
    {
        return [];
    }
}
