<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\Core\PurchaseOrderService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    #[\Livewire\Attributes\On('refresh-order-status')]
    public function refreshOrderStatus(): void
    {
        $this->fillForm();
    }

    protected function afterCreate(): void
    {
        // Get Purchase Order
        /** @var \App\Models\PurchaseOrder */
        $record = $this->getRecord();

        // Use service to handle business logic
        app(PurchaseOrderService::class)->syncOrderInfo($record->id);
    }
}
