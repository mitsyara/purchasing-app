<?php

namespace App\Services\InventoryAdjustment;

use App\Enums\InventoryTransactionDirectionEnum;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý nghiệp vụ cho InventoryAdjustment
 */
class InventoryAdjustmentService
{
    /**
     * Tạo hoặc cập nhật Inventory Adjustment với lines
     */
    public function createOrUpdate(array $data, ?InventoryAdjustment $adjustment = null): InventoryAdjustment
    {
        $linesInData = $data['lines_in'] ?? [];
        $linesOutData = $data['lines_out'] ?? [];
        
        // Xóa lines data khỏi main data
        unset($data['lines_in'], $data['lines_out']);
        
        return DB::transaction(function () use ($data, $adjustment, $linesInData, $linesOutData) {
            // Tạo hoặc update main record
            if ($adjustment) {
                $adjustment->update($data);
            } else {
                $data['created_by'] = auth()->id();
                $adjustment = InventoryAdjustment::create($data);
            }
            
            // Xử lý adjustment lines
            $this->handleAdjustmentLines($adjustment, $linesInData, $linesOutData);
            
            return $adjustment->fresh();
        });
    }

    /**
     * Load dữ liệu form edit - tách IN và OUT
     */
    public function loadFormData(InventoryAdjustment $record): array
    {
        $linesIn = [];
        $linesOut = [];

        // Nhóm lines theo product_id và qty direction
        $groupedLines = $record->adjustmentsLines->groupBy('product_id');

        foreach ($groupedLines as $productId => $productLines) {
            $inLots = [];
            $outLots = [];

            foreach ($productLines as $line) {
                $lotData = [
                    'id' => $line->id,
                    'lot_no' => $line->lot_no,
                    'mfg_date' => $line->mfg_date?->format('Y-m-d'),
                    'exp_date' => $line->exp_date?->format('Y-m-d'),
                    'adjustment_qty' => abs($line->adjustment_qty), // Luôn hiển thị số dương
                    'io_price' => $line->io_price,
                ];

                if ($line->adjustment_qty > 0) {
                    // IN (positive qty)
                    $inLots[] = $lotData;
                } else {
                    // OUT (negative qty) - lấy parent_transaction_id từ line
                    $lotData['parent_transaction_id'] = $line->parent_transaction_id;
                    $outLots[] = $lotData;
                }
            }

            // Thêm vào linesIn nếu có
            if (!empty($inLots)) {
                $linesIn[] = [
                    'product_id' => $productId,
                    'lots' => $inLots
                ];
            }

            // Thêm vào linesOut nếu có
            if (!empty($outLots)) {
                $linesOut[] = [
                    'product_id' => $productId,
                    'lots' => $outLots
                ];
            }
        }

        return [
            'company_id' => $record->company_id,
            'warehouse_id' => $record->warehouse_id,
            'adjustment_status' => $record->adjustment_status,
            'adjustment_date' => $record->adjustment_date?->format('Y-m-d'),
            'reason' => $record->reason,
            'notes' => $record->notes,
            'lines_in' => $linesIn,
            'lines_out' => $linesOut,
        ];
    }

    /**
     * Xử lý adjustment lines từ 2 repeater IN và OUT
     */
    protected function handleAdjustmentLines(InventoryAdjustment $adjustment, array $linesInData, array $linesOutData): void
    {
        // Lấy danh sách line IDs hiện tại
        $existingLineIds = $adjustment->adjustmentsLines()->pluck('id')->toArray();

        // Thu thập line IDs từ form data
        $submittedLineIds = $this->collectSubmittedLineIds($linesInData, $linesOutData);

        // Xóa lines không còn tồn tại trong form
        $this->deleteRemovedLines($existingLineIds, $submittedLineIds);

        // Xử lý lines IN (qty dương)
        $this->processLinesData($adjustment, $linesInData, true);
        
        // Xử lý lines OUT (qty âm)
        $this->processLinesData($adjustment, $linesOutData, false);
    }

    /**
     * Thu thập line IDs từ form data
     */
    protected function collectSubmittedLineIds(array $linesInData, array $linesOutData): array
    {
        $submittedLineIds = [];
        
        // Từ lines_in
        foreach ($linesInData as $lineData) {
            $lots = $lineData['lots'] ?? [];
            foreach ($lots as $lotData) {
                if (!empty($lotData['id'])) {
                    $submittedLineIds[] = $lotData['id'];
                }
            }
        }
        
        // Từ lines_out
        foreach ($linesOutData as $lineData) {
            $lots = $lineData['lots'] ?? [];
            foreach ($lots as $lotData) {
                if (!empty($lotData['id'])) {
                    $submittedLineIds[] = $lotData['id'];
                }
            }
        }

        return $submittedLineIds;
    }

    /**
     * Xóa lines đã bị remove khỏi form
     */
    protected function deleteRemovedLines(array $existingLineIds, array $submittedLineIds): void
    {
        $lineIdsToDelete = array_diff($existingLineIds, $submittedLineIds);

        if (!empty($lineIdsToDelete)) {
            // Xóa transactions của lines bị xóa
            InventoryTransaction::where('sourceable_type', InventoryAdjustmentLine::class)
                ->whereIn('sourceable_id', $lineIdsToDelete)
                ->delete();

            // Xóa lines bị xóa
            InventoryAdjustmentLine::whereIn('id', $lineIdsToDelete)->delete();
        }
    }

    /**
     * Xử lý data lines cho IN hoặc OUT
     */
    protected function processLinesData(InventoryAdjustment $adjustment, array $linesData, bool $isIn): void
    {
        foreach ($linesData as $lineData) {
            $productId = $lineData['product_id'];
            $lots = $lineData['lots'] ?? [];

            foreach ($lots as $lotData) {
                $qty = $lotData['adjustment_qty'];
                
                // Chuyển đổi qty theo direction: IN = positive, OUT = negative
                $finalQty = $isIn ? abs($qty) : -abs($qty);

                // Nếu qty = 0, xóa line khỏi DB (nếu có)
                if ($qty == 0) {
                    $this->deleteLineIfExists($lotData);
                    continue;
                }

                // Chuẩn bị data để lưu
                $lineDataToSave = $this->prepareLineData($productId, $finalQty, $lotData, $isIn);

                // Create hoặc update line
                $this->createOrUpdateLine($adjustment, $lineDataToSave, $lotData, $isIn);
            }
        }
    }

    /**
     * Xóa line nếu tồn tại
     */
    protected function deleteLineIfExists(array $lotData): void
    {
        if (!empty($lotData['id'])) {
            $lineToDelete = InventoryAdjustmentLine::find($lotData['id']);
            if ($lineToDelete) {
                // Xóa transaction trước
                InventoryTransaction::where('sourceable_type', InventoryAdjustmentLine::class)
                    ->where('sourceable_id', $lineToDelete->id)
                    ->delete();

                // Xóa line
                $lineToDelete->delete();
            }
        }
    }

    /**
     * Chuẩn bị dữ liệu line để lưu
     */
    protected function prepareLineData(int $productId, float $finalQty, array $lotData, bool $isIn): array
    {
        $lineDataToSave = [
            'product_id' => $productId,
            'adjustment_qty' => $finalQty,
            'io_price' => $lotData['io_price'] ?? null,
        ];

        if ($isIn) {
            // IN: lấy từ form trực tiếp
            $lineDataToSave = array_merge($lineDataToSave, [
                'lot_no' => $lotData['lot_no'],
                'mfg_date' => $lotData['mfg_date'] ?? null,
                'exp_date' => $lotData['exp_date'] ?? null,
                'parent_transaction_id' => null, // IN không có parent
            ]);
        } else {
            // OUT: lấy từ parent transaction + lưu parent_transaction_id
            $parentTransactionId = $lotData['parent_transaction_id'];
            $parentTransaction = InventoryTransaction::find($parentTransactionId);
            if ($parentTransaction) {
                $lineDataToSave = array_merge($lineDataToSave, [
                    'lot_no' => $parentTransaction->lot_no,
                    'mfg_date' => $parentTransaction->mfg_date,
                    'exp_date' => $parentTransaction->exp_date,
                    'parent_transaction_id' => $parentTransactionId, // Lưu để mapping
                ]);
            }
        }

        return $lineDataToSave;
    }

    /**
     * Tạo hoặc cập nhật line
     */
    protected function createOrUpdateLine(InventoryAdjustment $adjustment, array $lineDataToSave, array $lotData, bool $isIn): void
    {
        if (!empty($lotData['id'])) {
            // Cập nhật line existing
            $existingLine = InventoryAdjustmentLine::find($lotData['id']);
            if ($existingLine) {
                $existingLine->update($lineDataToSave);
                $this->handleLineTransaction($adjustment, $existingLine, $lotData, $isIn);
            }
        } else {
            // Tạo line mới
            $newLine = $adjustment->adjustmentsLines()->create($lineDataToSave);
            $this->handleLineTransaction($adjustment, $newLine, $lotData, $isIn);
        }
    }

    /**
     * Xử lý inventory transaction cho adjustment line
     */
    protected function handleLineTransaction(
        InventoryAdjustment $adjustment,
        InventoryAdjustmentLine $adjustmentLine,
        array $lotData,
        bool $isIn
    ): void {
        // Tìm transaction existing của line này
        $existingTransaction = InventoryTransaction::where('sourceable_type', InventoryAdjustmentLine::class)
            ->where('sourceable_id', $adjustmentLine->id)
            ->first();

        // Xử lý theo status
        if ($adjustment->adjustment_status === \App\Enums\OrderStatusEnum::Canceled) {
            // Status = Canceled: Xóa transaction
            if ($existingTransaction) {
                $existingTransaction->delete();
            }
            return;
        }

        // Chuẩn bị data cho transaction
        $transactionData = $this->prepareTransactionData($adjustment, $adjustmentLine);

        // Nếu là OUT, gán parent_id từ form data
        if (!$isIn && !empty($lotData['parent_transaction_id'])) {
            $transactionData['parent_id'] = $lotData['parent_transaction_id'];
        }

        if ($existingTransaction) {
            // Cập nhật transaction existing
            $existingTransaction->update($transactionData);
        } else {
            // Tạo transaction mới
            InventoryTransaction::create(array_merge($transactionData, [
                'sourceable_id' => $adjustmentLine->id,
                'sourceable_type' => InventoryAdjustmentLine::class,
            ]));
        }
    }

    /**
     * Chuẩn bị dữ liệu cho inventory transaction
     */
    protected function prepareTransactionData(
        InventoryAdjustment $adjustment,
        InventoryAdjustmentLine $adjustmentLine
    ): array {
        $qty = $adjustmentLine->adjustment_qty;
        
        // Xác định hướng transaction dựa trên qty
        $direction = $qty > 0
            ? InventoryTransactionDirectionEnum::Import
            : InventoryTransactionDirectionEnum::Export;

        // Xác định trạng thái checked dựa trên adjustment status
        $isChecked = false;
        $checkedBy = null;

        if ($adjustment->adjustment_status === \App\Enums\OrderStatusEnum::Completed) {
            $isChecked = true;
            $checkedBy = auth()->id();
        }

        return [
            'company_id' => $adjustment->company_id,
            'warehouse_id' => $adjustment->warehouse_id,
            'product_id' => $adjustmentLine->product_id,

            'transaction_direction' => $direction,
            'qty' => abs($qty), // Lưu qty dương

            'lot_no' => $adjustmentLine->lot_no,
            'mfg_date' => $adjustmentLine->mfg_date,
            'exp_date' => $adjustmentLine->exp_date,

            'io_price' => $adjustmentLine->io_price,
            'io_currency' => 'VND', // Mặc định VND
            'break_price' => $qty > 0 ? $adjustmentLine->io_price : null,

            'transaction_date' => $adjustment->adjustment_date,

            'is_checked' => $isChecked,
            'checked_by' => $checkedBy,

            'notes' => "{$adjustment->reason}",
        ];
    }
}