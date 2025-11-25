<?php

namespace App\Filament\Resources\InventoryAdjustments\Helpers;

use App\Models\InventoryAdjustment;
use App\Models\InventoryTransaction;
use App\Services\InventoryAdjustment\InventoryAdjustmentService;
use Filament\Actions\Action;

/**
 * Helper tập trung cho Resource Form/Table
 */
class InventoryAdjustmentResourceHelper
{
    protected InventoryAdjustmentService $service;

    public function __construct(InventoryAdjustmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Đồng bộ dữ liệu cho action create/edit
     */
    public function syncData(Action $action, array $data): array
    {
        // Determine if this is create or edit
        $isCreate = $action instanceof \Filament\Actions\CreateAction;
        
        if ($isCreate) {
            // Create: set created_by and handle after creation
            $data['created_by'] = auth()->id();
            
            $action->after(function (InventoryAdjustment $record) use ($data) {
                $this->service->createOrUpdate($data, $record);
            });
        } else {
            // Edit: handle after update
            $action->after(function (InventoryAdjustment $record) use ($data) {
                $this->service->createOrUpdate($data, $record);
            });
        }

        // Xóa data không cần thiết cho main model
        unset($data['lines_in'], $data['lines_out'], $data['id']);
        
        return $data;
    }

    /**
     * Load dữ liệu cho form edit
     */
    public function loadFormData(InventoryAdjustment $record): array
    {
        return $this->service->loadFormData($record);
    }

    /**
     * Lấy danh sách available lots cho OUT adjustment (Form helper)
     */
    public function getAvailableLotsForOut(int $warehouseId, int $productId): array
    {
        if (!$warehouseId || !$productId) {
            return [];
        }

        return InventoryTransaction::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Import)
            ->whereRaw('qty > COALESCE((SELECT SUM(qty) FROM inventory_transactions children WHERE children.parent_id = inventory_transactions.id), 0)')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->mapWithKeys(function ($transaction) {
                return [
                    $transaction->id => $transaction->lot_fifo
                ];
            })
            ->toArray();
    }

    /**
     * Populate data after product select (IN)
     */
    public function populateDataFromProductIn(?string $state, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        // Reset lots
        $set('lots', [
            [
                'lot_no' => null,
                'mfg_date' => null,
                'exp_date' => null,
                'adjustment_qty' => null,
                'io_price' => null,
            ]
        ]);
    }

    /**
     * Populate data after product select (OUT)
     */
    public function populateDataFromProductOut(?string $state, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        // Reset lots
        $set('lots', [
            [
                'parent_transaction_id' => null,
                'lot_no' => null,
                'mfg_date' => null,
                'exp_date' => null,
                'adjustment_qty' => null,
                'io_price' => null,
            ]
        ]);
    }

    /**
     * Lấy danh sách available lots cho OUT adjustment (callable wrapper)
     */
    public function getAvailableLotsForOutCallable(callable $get): array
    {
        $warehouseId = $get('../../../../warehouse_id');
        $productId = $get('../../product_id');

        return $this->getAvailableLotsForOut($warehouseId, $productId);
    }

    /**
     * Populate data from parent transaction
     */
    public function populateDataFromParentTransaction(?string $parentId, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        if (!$parentId) {
            $set('lot_no', null);
            $set('mfg_date', null);
            $set('exp_date', null);
            return;
        }

        $transaction = InventoryTransaction::find($parentId);
        if ($transaction) {
            $set('lot_no', $transaction->lot_no);
            $set('mfg_date', $transaction->mfg_date?->format('Y-m-d'));
            $set('exp_date', $transaction->exp_date?->format('Y-m-d'));
        }
    }
}
