<?php

namespace App\Filament\Schemas\SettingsClusters;

use Filament\Schemas\Schema;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;

class PackingSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\TextInput::make('packing_name')
                    ->required()
                    ->label('Packing Name')
                    ->placeholder('Enter Packing Name'),

                S\FusedGroup::make([
                    F\TextInput::make('unit_conversion_value')
                        ->label('Conversion Value')
                        ->numeric()
                        ->placeholder('Conversion Value'),

                    F\Select::make('unit_id')
                        ->label('Unit')
                        ->options(fn() => \App\Models\Unit::all()->pluck('unit_name', 'id'))
                        ->searchable()
                        ->required()
                        ->placeholder('Select Unit'),
                ])
                    ->label('Conversion')
                    ->columns(2),

                __notes(),
            ]);
    }
}
