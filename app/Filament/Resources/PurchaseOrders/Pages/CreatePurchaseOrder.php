<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\PurchaseOrder\CallAllPurchaseOrderServices;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterCreate(): void
    {
        // Get Purchase Order
        /** @var \App\Models\PurchaseOrder */
        $record = $this->getRecord();

        // Log the user who created the record
        $record->updateQuietly([
            'created_by' => auth()->id(),
        ]);

        // Call Services
        new CallAllPurchaseOrderServices($record);
    }
}
