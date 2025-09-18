<?php

namespace App\Filament\Schemas\SettingsClusters;

use Filament\Schemas\Schema;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;

class UnitSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Group::make([
                    F\TextInput::make('unit_code')
                        ->required()
                        ->label('Unit Code')
                        ->placeholder('Code'),

                    F\TextInput::make('unit_name')
                        ->required()
                        ->label('Unit Name')
                        ->placeholder('Name'),
                ])
                    ->columns(2),

                S\FusedGroup::make([
                    F\TextInput::make('conversion_factor')
                        ->requiredWith(['parent_id'])
                        ->label('Conversion Factor')
                        ->placeholder('1.0'),

                    F\Select::make('parent_id')
                        ->label('Conversion Unit')
                        ->relationship('parent', 'unit_name'),
                ])
                    ->label('Conversion of')
                    ->columns(2),

                F\Textarea::make('unit_notes')
                    ->label('Unit Notes')
                    ->rows(4),
            ]);
    }
}
