<?php

namespace App\Filament\Resources\InventoryTransfers\Pages;

use App\Filament\Resources\InventoryTransfers\InventoryTransferResource;
use App\Services\InventoryTransfer\InventoryTransferService;
use App\Models\InventoryTransfer;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageInventoryTransfers extends ManageRecords
{
    protected static string $resource = InventoryTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                ->using(function (array $data) {
                    /** @var InventoryTransfer $record */
                    $record = static::getModel()::create($data);
                    
                    // Đồng bộ inventory transactions
                    $service = app(InventoryTransferService::class);
                    $service->syncInventoryTransactions($record);
                    
                    return $record;
                }),
        ];
    }

    /**
     * Xử lý sau khi cập nhật record
     */
    protected function afterSave(Model $record): void
    {
        /** @var InventoryTransfer $record */
        $service = app(InventoryTransferService::class);
        $service->syncInventoryTransactions($record);
    }

    /**
     * Xử lý trước khi xoá record
     */
    protected function beforeDelete(Model $record): void
    {
        /** @var InventoryTransfer $record */
        $service = app(InventoryTransferService::class);
        $service->handleTransferDeletion($record);
    }
}
