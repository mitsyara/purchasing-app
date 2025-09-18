<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OrderStatusEnum: string implements HasLabel, HasIcon, HasColor
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Canceled = 'canceled';
    case Completed = 'completed';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Sent => __('Sent'),
            self::Canceled => __('Canceled'),
            self::Completed => __('Completed'),
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Draft => 'heroicon-o-document-text',
            self::Sent => 'heroicon-o-paper-airplane',
            self::Canceled => 'heroicon-o-ban',
            self::Completed => 'heroicon-o-check',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Canceled => 'red',
            self::Completed => 'green',
        };
    }
}
