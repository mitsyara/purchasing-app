<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Filament\Resources\SalesOrders\SalesOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function afterCreate(): void
    {
        // Get Sales Order
        $record = $this->getRecord();
        $record->updateQuietly(['created_by' => auth()->id()]);
        // Call Services to update project totals
        app(\App\Services\SalesOrder\SalesOrderService::class)->syncOrderInfo($record->id);
    }
}
