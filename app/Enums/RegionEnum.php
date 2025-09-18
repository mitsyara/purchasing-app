<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum RegionEnum: string implements HasLabel, HasIcon, HasColor
{
    case North = 'north';
    case Central = 'central';
    case South = 'south';
    case Other = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::North => 'North',
            self::Central => 'Central',
            self::South => 'South',
            self::Other => 'Other',
        };
    }

    public function getIcon(): ?string
    {
        return 'heroicon-o-map-pin';
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::North => 'blue',
            self::Central => 'orange',
            self::South => 'yellow',
            self::Other => 'gray',
        };
    }
}
