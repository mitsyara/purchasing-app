<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaytermDelayAtEnum: string implements HasLabel, HasColor, HasIcon
{
    case OrderDate = 'order_date';
    case ATD = 'atd';
    case ATA = 'ata';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OrderDate => __('Order Date'),
            self::ATD => __('ATD'),
            self::ATA => (__('ATA')),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OrderDate => 'danger',
            self::ATD => 'warning',
            self::ATA => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OrderDate => 'heroicon-s-document-text',
            self::ATD => 'heroicon-s-truck',
            self::ATA => 'heroicon-s-building-storefront',
        };
    }

    public static function cases(): array
    {
        $arr = [];
        foreach (static::cases() as $case) {
            $arr[] = $case->value;
        }
        return $arr;
    }
}
