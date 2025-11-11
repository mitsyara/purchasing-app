<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DeliveryStatusEnum: string implements HasLabel, HasIcon, HasColor
{
    case Scheduled = 'scheduled';
    case Partial = 'partial';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => __('Scheduled'),
            self::Partial => __('Partial'),
            self::Completed => __('Completed'),
            self::Canceled => __('Canceled'),
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Scheduled => 'heroicon-o-calendar',
            self::Partial => 'heroicon-o-truck',
            self::Completed => 'heroicon-o-check-circle',
            self::Canceled => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Partial => 'warning',
            self::Completed => 'success',
            self::Canceled => 'danger',
        };
    }
}
