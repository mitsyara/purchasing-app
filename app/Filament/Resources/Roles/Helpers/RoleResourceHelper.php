<?php

namespace App\Filament\Resources\Roles\Helpers;

use App\Filament\Resources\Roles\Helpers\RoleFormHelper;

/**
 * Resource Helper - logic hỗ trợ cho Resource
 */
trait RoleResourceHelper
{
    use RoleFormHelper;

    /**
     * Wrapper methods để maintain compatibility với Resource
     */
    protected static function getRoleForm(): array
    {
        return static::roleFormSchema();
    }

    /**
     * Business logic helpers có thể thêm vào đây
     */
}