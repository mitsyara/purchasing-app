<?php

namespace App\Filament\Schemas\SettingsClusters;

use Filament\Schemas\Schema;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;

class VatSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Group::make([
                    F\TextInput::make('vat_name')
                        ->required()
                        ->label('VAT Name')
                        ->placeholder('Name'),

                    F\TextInput::make('vat_value')
                        ->label('VAT Value')
                        ->numeric()
                        ->suffix('%')
                        ->placeholder('Value'),
                ])
                    ->columns(),

                F\Textarea::make('vat_notes')
                    ->label('VAT Notes')
                    ->rows(4),
            ]);
    }
}
