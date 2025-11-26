<?php

namespace App\Filament\Resources\SalesDeliveryScheduleLines\Helpers;

use App\Models\SalesDeliveryScheduleLine;
use Filament\Actions as A;
use Filament\Support\Enums\Width;

/**
 * Helper trait cho SalesDeliveryScheduleLine Resource
 * Cung cấp các method hỗ trợ cho form và business logic
 */
trait SalesDeliveryScheduleLineResourceHelper
{
    use SalesDeliveryScheduleLineFormHelper;

    /**
     * Action để chỉnh sửa trạng thái delivery schedule
     */
    protected static function editScheduleStatus(): A\Action
    {
        return A\Action::make('switch_status')
            ->modal()
            ->modalWidth(Width::Large)
            ->schema(static::deliveryStatusSchema())
            ->fillForm(fn($record) => ['delivery_status' => $record->deliverySchedule->delivery_status])
            ->action(function (SalesDeliveryScheduleLine $record, array $data): void {
                if ($data['delivery_status']) {
                    $record->deliverySchedule->update(['delivery_status' => $data['delivery_status']]);
                }
            });
    }
}