<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OrderStatusEnum: string implements HasLabel, HasIcon, HasColor
{
    case Draft = 'draft';
    case Inprogress = 'inprogress';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Inprogress => __('Inprogress'),
            self::Canceled => __('Canceled'),
            self::Completed => __('Completed'),
        };
    }

    public function getIcon(): string|\BackedEnum|null
    {
        return match ($this) {
            self::Draft => 'heroicon-o-document-text',
            self::Inprogress => 'heroicon-o-paper-airplane',
            self::Canceled => 'heroicon-o-x-circle',
            self::Completed => 'heroicon-o-check',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Inprogress => 'info',
            self::Canceled => 'red',
            self::Completed => 'green',
        };
    }
}
