<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PortTypeEnum: string implements HasLabel, HasColor
{
    //
    case Sea = 'sea';
    case Air = 'air';
    case Land = 'land';
    case Other = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Sea => __('Sea'),
            self::Air => __('Air'),
            self::Land => __('Land'),
            self::Other => __('Other'),
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Sea => 'info',
            self::Air => 'success',
            self::Land => 'warning',
            self::Other => 'pink',
        };
    }
}
