<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CustomsClearanceStatusEnum: string implements HasLabel, HasIcon, HasColor
{
    case PermissionGranting = 'permission_granting';
    case PermissionGranted = 'permission_granted';
    case CustomsDeclaring = 'customs_declaring';
    case CustomsDeclared = 'customs_declared';
    case WarehouseStorage = 'warehouse_storage';
    case ReleaseGoods = 'release_goods';
    case Complete = 'complete';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PermissionGranting => __('Import Licensing'),
            self::PermissionGranted => __('Import Licensed'),
            self::CustomsDeclaring => __('Customs Declaring'),
            self::CustomsDeclared => __('Customs Declared'),
            self::ReleaseGoods => __('Release Goods'),
            self::WarehouseStorage => __('Warehouse Storage'),
            self::Complete => __('Complete'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PermissionGranting => 'gray',
            self::PermissionGranted => 'cyan',
            self::CustomsDeclaring => 'yellow',
            self::CustomsDeclared => 'blue',
            self::ReleaseGoods => 'fuchsia',
            self::WarehouseStorage => 'red',
            self::Complete => 'green',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PermissionGranting => 'heroicon-o-document-text',
            self::PermissionGranted => 'heroicon-o-document-check',
            self::CustomsDeclaring => 'heroicon-o-chat-bubble-bottom-center-text',
            self::CustomsDeclared => 'heroicon-o-clipboard-document-check',
            self::ReleaseGoods => 'heroicon-s-shield-check',
            self::WarehouseStorage => 'heroicon-o-truck',
            self::Complete => 'heroicon-s-check-circle',
        };
    }
}
