<?php

namespace App\Services\InventoryTransfer;

use App\Models\InventoryTransfer;
use App\Models\InventoryTransferLine;
use App\Models\InventoryTransaction;
use App\Enums\OrderStatusEnum;
use App\Enums\InventoryTransactionDirectionEnum;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Inventory Transfer
 * Không chứa logic UI/Form - chỉ pure business logic
 */
class InventoryTransferService
{
    /**
     * Xử lý đồng bộ InventoryTransaction sau khi tạo/cập nhật Transfer
     */
    public function syncInventoryTransactions(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            // Tính toán và cập nhật extra costs trước
            $this->calculateExtraCosts($transfer);

            if ($transfer->transfer_status === OrderStatusEnum::Canceled) {
                // Nếu status là Cancelled, xoá tất cả transactions liên quan
                $this->deleteRelatedTransactions($transfer);
            } else {
                // Sync transactions
                $this->syncTransactions($transfer);
                
                // Nếu status là Completed, đánh dấu transactions đã check
                if ($transfer->transfer_status === OrderStatusEnum::Completed) {
                    $this->markTransactionsAsChecked($transfer);
                }
            }
        });
    }

    /**
     * Xoá các InventoryTransaction liên quan đến transfer
     */
    protected function deleteRelatedTransactions(InventoryTransfer $transfer): void
    {
        InventoryTransaction::query()
            ->where('sourceable_type', InventoryTransferLine::class)
            ->whereIn('sourceable_id', $transfer->transferLines()->pluck('id'))
            ->delete();
    }

    /**
     * Tính toán và cập nhật total_extra_cost và average_extra_cost_per_unit
     */
    protected function calculateExtraCosts(InventoryTransfer $transfer): void
    {
        // Tính total_extra_cost
        $totalExtraCost = collect($transfer->extra_costs ?? [])
            ->sum(fn($cost) => $cost['amount'] ?? 0);

        // Tính tổng số lượng transfer
        $totalTransferQty = $transfer->transferLines()->sum('transfer_qty');

        // Tính average_extra_cost_per_unit
        $avgExtraCost = $totalTransferQty > 0 ? $totalExtraCost / $totalTransferQty : 0;

        $transfer->update([
            'total_extra_cost' => $totalExtraCost,
            'average_extra_cost_per_unit' => $avgExtraCost,
        ]);
    }

    /**
     * Sync transactions
     */
    protected function syncTransactions(InventoryTransfer $transfer): void
    {
        // Lấy tất cả transactions hiện có của transfer
        $existingTransactions = InventoryTransaction::query()
            ->where('sourceable_type', InventoryTransferLine::class)
            ->whereIn('sourceable_id', $transfer->transferLines()->pluck('id'))
            ->get()
            ->keyBy(fn($transaction) => $transaction->sourceable_id . '_' . $transaction->transaction_direction->value);

        // Xoá transactions của các lines không còn tồn tại
        $currentLineIds = $transfer->transferLines()->pluck('id')->toArray();
        InventoryTransaction::query()
            ->where('sourceable_type', InventoryTransferLine::class)
            ->whereNotIn('sourceable_id', $currentLineIds)
            ->where(function ($query) use ($transfer) {
                $query->where('warehouse_id', $transfer->from_warehouse_id)
                      ->orWhere('warehouse_id', $transfer->to_warehouse_id);
            })
            ->delete();

        // Sync từng line
        foreach ($transfer->transferLines as $line) {
            $lot = $line->lot;
            if (!$lot) continue;

            $this->syncLineTransactions($transfer, $line, $lot, $existingTransactions);
        }
    }

    /**
     * Sync transactions cho một transfer line cụ thể
     */
    protected function syncLineTransactions(
        InventoryTransfer $transfer,
        InventoryTransferLine $line,
        InventoryTransaction $lot,
        $existingTransactions
    ): void {
        $importPrice = $lot->break_price + $transfer->average_extra_cost_per_unit;
        
        // Base data cho transactions
        $baseData = [
            'company_id' => $transfer->company_id,
            'product_id' => $lot->product_id,
            'sourceable_type' => InventoryTransferLine::class,
            'sourceable_id' => $line->id,
            'parent_id' => $lot->id,
            'transaction_date' => $transfer->transfer_date,
            'qty' => $line->transfer_qty,
            'lot_no' => $lot->lot_no,
            'mfg_date' => $lot->mfg_date,
            'exp_date' => $lot->exp_date,
            'io_currency' => 'VND',
        ];

        // Sync EXPORT transaction
        $exportKey = $line->id . '_export';
        $exportTransaction = $existingTransactions->get($exportKey);
        
        $exportData = array_merge($baseData, [
            'warehouse_id' => $transfer->from_warehouse_id,
            'transaction_direction' => InventoryTransactionDirectionEnum::Export,
            'break_price' => $lot->break_price,
            'io_price' => $lot->break_price,
            'is_checked' => $transfer->transfer_status === OrderStatusEnum::Completed,
        ]);

        if ($exportTransaction) {
            $exportTransaction->update($exportData);
        } else {
            InventoryTransaction::create($exportData);
        }

        // Sync IMPORT transaction
        $importKey = $line->id . '_import';
        $importTransaction = $existingTransactions->get($importKey);
        
        $importData = array_merge($baseData, [
            'warehouse_id' => $transfer->to_warehouse_id,
            'transaction_direction' => InventoryTransactionDirectionEnum::Import,
            'break_price' => $importPrice,
            'io_price' => $importPrice,
            'is_checked' => $transfer->transfer_status === OrderStatusEnum::Completed,
        ]);

        if ($importTransaction) {
            $importTransaction->update($importData);
        } else {
            InventoryTransaction::create($importData);
        }
    }

    /**
     * Đánh dấu các transaction đã check khi status = Completed
     */
    protected function markTransactionsAsChecked(InventoryTransfer $transfer): void
    {
        $transferLineIds = $transfer->transferLines()->pluck('id');

        InventoryTransaction::query()
            ->where('sourceable_type', InventoryTransferLine::class)
            ->whereIn('sourceable_id', $transferLineIds)
            ->update([
                'is_checked' => true,
                'checked_by' => auth()->id(),
            ]);
    }

    /**
     * Xử lý khi xoá Transfer
     */
    public function handleTransferDeletion(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $this->deleteRelatedTransactions($transfer);
        });
    }
}