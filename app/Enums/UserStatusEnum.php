<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserStatusEnum: string implements HasLabel, HasIcon, HasColor
{
    case Inactive = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Inactive => 'Inactive',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Inactive => 'heroicon-o-clock',
            self::Active => 'heroicon-o-check-circle',
            self::Suspended => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Inactive => 'warning',
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }
}
