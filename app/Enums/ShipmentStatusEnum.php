<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ShipmentStatusEnum: string implements HasLabel, HasIcon, HasColor
{
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InTransit => 'In Transit',
            self::Arrived => 'Arrived',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::InTransit => 'heroicon-o-truck',
            self::Arrived => 'heroicon-o-play',
            self::Delivered => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InTransit => 'blue',
            self::Arrived => 'yellow',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }
}
