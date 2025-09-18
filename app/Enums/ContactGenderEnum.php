<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactGenderEnum: string implements HasLabel
{
    case Mr = 'mr';
    case Ms = 'ms';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mr => 'Mr.',
            self::Ms => 'Ms.',
            self::Other => 'Other',
        };
    }

}
