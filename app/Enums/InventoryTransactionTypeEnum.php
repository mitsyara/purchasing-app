<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InventoryTransactionTypeEnum: string implements HasLabel, HasIcon, HasColor
{
    case Import = 'import';
    case Export = 'export';

    public function getLabel(): string
    {
        return match ($this) {
            self::Import => __('Import'),
            self::Export => __('Export'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Import => 'heroicon-o-arrow-down',
            self::Export => 'heroicon-o-arrow-up',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Import => 'success',
            self::Export => 'danger',
        };
    }
}
