<?php

namespace App\Filament\Resources\SalesDeliveryScheduleLines\Helpers;

use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

/**
 * Helper trait cho các form schemas của SalesDeliveryScheduleLine Resource
 */
trait SalesDeliveryScheduleLineFormHelper
{
    /**
     * Form schema cho chỉnh sửa trạng thái delivery schedule
     */
    protected static function deliveryStatusSchema(): array
    {
        return [
            ToggleButtons::make('delivery_status')
                ->label(__('Delivery Status'))
                ->options(\App\Enums\DeliveryStatusEnum::class)
                ->grouped()
                ->required(),
        ];
    }
}