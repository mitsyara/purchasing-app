<?php

namespace App\Services\SalesShipment;

use App\Models\InventoryTransaction;
use App\Models\SalesDeliverySchedule;
use App\Models\SalesDeliveryScheduleLine;
use App\Models\SalesShipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Sales Shipment
 * - Sync giữa InventoryTransaction và SalesDeliveryScheduleLine
 * - Tạo InventoryTransaction xuất kho từ form data transactions
 */
class SalesShipmentService
{
    // -------------------------- SALES SHIPMENT SERVICES --------------------------

    /**
     * Xử lý mapping transactions sau khi SalesShipment đã được tạo bởi Filament
     * 
     * @param SalesShipment $shipment
     * @param array $transactionsData Form data transactions từ Filament
     * @return Collection<InventoryTransaction>
     */
    public function syncShipmentTransactions(SalesShipment $shipment, array $transactionsData): Collection
    {
        return DB::transaction(function () use ($shipment, $transactionsData) {
            // Xóa các transactions cũ nếu có
            $this->clearShipmentTransactions($shipment);

            // Tạo các transactions mới
            $createdTransactions = collect();
            foreach ($transactionsData as $transactionData) {
                $transaction = $this->createShipmentTransaction(
                    $shipment,
                    $transactionData['schedule_line_id'],
                    $transactionData['inventory_transaction_id'],
                    $transactionData['qty']
                );
                $createdTransactions->push($transaction);
            }

            return $createdTransactions;
        });
    }

    /**
     * Tạo InventoryTransaction xuất kho cho shipment
     * 
     * @param SalesShipment $shipment
     * @param int $scheduleLineId
     * @param int $parentTransactionId ID của inventory transaction (lot gốc)
     * @param float $qty Số lượng xuất
     * @return InventoryTransaction
     */
    protected function createShipmentTransaction(
        SalesShipment $shipment,
        int $scheduleLineId,
        int $parentTransactionId,
        float $qty
    ): InventoryTransaction {
        // Lấy thông tin lot gốc
        $parentTransaction = InventoryTransaction::findOrFail($parentTransactionId);

        // Lấy thông tin schedule line
        $scheduleLine = SalesDeliveryScheduleLine::findOrFail($scheduleLineId);

        // Tạo transaction xuất kho (child của lot gốc)
        $transaction = InventoryTransaction::create([
            'company_id' => $shipment->company_id ?? $shipment->deliverySchedules->first()?->company?->id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $parentTransaction->product_id,

            'sourceable_id' => $shipment->id,
            'sourceable_type' => SalesShipment::class,
            'parent_id' => $parentTransactionId,

            'transaction_direction' => \App\Enums\InventoryTransactionDirectionEnum::Export,
            'qty' => $qty,

            'lot_no' => $parentTransaction->lot_no,
            'mfg_date' => $parentTransaction->mfg_date,
            'exp_date' => $parentTransaction->exp_date,

            'break_price' => $parentTransaction->break_price,
            'io_price' => $scheduleLine->unit_price,
            'io_currency' => $scheduleLine->salesOrder->currency ?? 'VND',

            'notes' => "Xuất kho cho lô hàng #{$shipment->id}",
        ]);

        // Sync với schedule line qua bảng pivot
        $transaction->salesScheduleLines()->attach($scheduleLineId, [
            'qty' => $qty
        ]);

        return $transaction;
    }

    /**
     * Xóa các transactions cũ của shipment
     * 
     * @param SalesShipment $shipment
     * @return void
     */
    protected function clearShipmentTransactions(SalesShipment $shipment): void
    {
        // Xóa mapping trong bảng pivot trước
        $oldTransactions = $shipment->transactions;
        foreach ($oldTransactions as $transaction) {
            $transaction->salesScheduleLines()->detach();
        }

        // Xóa các inventory transactions
        $shipment->transactions()->delete();
    }

    /**
     * Lấy danh sách lot có thể xuất cho sản phẩm/assortment (sử dụng custom query)
     * 
     * @param array $productIds
     * @param int $warehouseId
     * @return Collection<InventoryTransaction>
     */
    public function getAvailableLots(array $productIds, int $warehouseId): Collection
    {
        return InventoryTransaction::query()
            ->forProducts($productIds)
            ->inWarehouse($warehouseId)
            ->availableForExport()
            ->with(['product'])
            ->get();
    }

    /**
     * Lấy options cho schedule line selection với remaining qty
     * 
     * @param array $scheduleIds
     * @param int|null $excludeShipmentId Loại trừ shipment đang edit
     * @return array
     */
    public function getScheduleLineOptions(array $scheduleIds, ?int $excludeShipmentId = null): array
    {
        return SalesDeliveryScheduleLine::query()
            ->getFormOptionsWithRemaining($scheduleIds, $excludeShipmentId);
    }

    /**
     * Lấy product IDs từ schedule line
     * 
     * @param int $scheduleLineId
     * @return array
     */
    public function getProductIdsFromScheduleLine(int $scheduleLineId): array
    {
        return SalesDeliveryScheduleLine::query()
            ->getProductIds($scheduleLineId);
    }

    /**
     * Lấy unique delivery addresses từ selected schedules
     * 
     * @param array $scheduleIds
     * @return array
     */
    public function getDeliveryAddresses(array $scheduleIds): array
    {
        return SalesDeliverySchedule::query()
            ->getDeliveryAddresses($scheduleIds);
    }

    /**
     * Load data transactions cho form edit
     * 
     * @param SalesShipment $shipment
     * @return array
     */
    public function loadTransactionsData(SalesShipment $shipment): array
    {
        $transactionsData = [];

        // Load shipment với relationships cần thiết
        $shipment = SalesShipment::with([
            'transactions.salesScheduleLines',
            'transactions.parent'
        ])->find($shipment->id);

        foreach ($shipment->transactions as $transaction) {
            foreach ($transaction->salesScheduleLines as $scheduleLine) {
                $transactionsData[] = [
                    'schedule_line_id' => $scheduleLine->id,
                    'inventory_transaction_id' => $transaction->parent_id, // Luôn lấy parent_id (lot gốc)
                    'qty' => $scheduleLine->pivot->qty ?? $transaction->qty,
                ];
            }
        }

        return $transactionsData;
    }

    /**
     * Lấy remaining quantity của inventory transaction
     * 
     * @param int $transactionId
     * @param int|null $excludeShipmentId
     * @return float
     */
    public function getInventoryTransactionRemaining(int $transactionId, ?int $excludeShipmentId = null): float
    {
        $transaction = InventoryTransaction::find($transactionId);

        if (!$transaction) {
            return 0;
        }

        // Lấy danh sách transaction IDs cần exclude
        $excludeTransactionIds = $excludeShipmentId ? $this->getShipmentTransactionIds($excludeShipmentId) : [];

        // Tính shipped qty không tính các transactions đang edit
        $shippedQty = $transaction->children()
            ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export)
            ->when(!empty($excludeTransactionIds), function ($query) use ($excludeTransactionIds) {
                $query->whereNotIn('id', $excludeTransactionIds);
            })
            ->sum('qty');

        return $transaction->qty - $shippedQty;
    }

    /**
     * Lấy remaining quantity của schedule line
     * 
     * @param int $scheduleLineId
     * @param int|null $excludeShipmentId
     * @return float
     */
    public function getScheduleLineRemaining(int $scheduleLineId, ?int $excludeShipmentId = null): float
    {
        $scheduleLine = SalesDeliveryScheduleLine::find($scheduleLineId);
        if (!$scheduleLine) {
            return 0;
        }

        // Tính tổng đã xuất (loại trừ shipment đang edit)
        $shippedQty = DB::table('sales_shipment_transactions as sst')
            ->join('inventory_transactions as it', 'sst.inventory_transaction_id', '=', 'it.id')
            ->where('sst.sales_delivery_schedule_line_id', $scheduleLineId)
            ->when($excludeShipmentId, function ($query) use ($excludeShipmentId) {
                $query->where('it.sourceable_id', '!=', $excludeShipmentId)
                    ->where('it.sourceable_type', SalesShipment::class);
            })
            ->sum('sst.qty');

        return $scheduleLine->qty - ($shippedQty ?: 0);
    }

    /**
     * Tính toán qty tối ưu để fill vào form
     * 
     * @param int $transactionId
     * @param int $scheduleLineId
     * @param int|null $excludeShipmentId
     * @return float
     */
    public function calculateOptimalQty(int $transactionId, int $scheduleLineId, ?int $excludeShipmentId = null): float
    {
        $transactionRemaining = $this->getInventoryTransactionRemaining($transactionId, $excludeShipmentId);
        $scheduleLineRemaining = $this->getScheduleLineRemaining($scheduleLineId, $excludeShipmentId);

        // Lấy min của 2 values để không vượt quá bất kỳ limit nào
        return min($transactionRemaining, $scheduleLineRemaining);
    }

    /**
     * Validate transactions data trước khi xử lý
     * 
     * @param array $transactionsData
     * @return array Errors nếu có
     */
    public function validateTransactionsData(array $transactionsData): array
    {
        $errors = [];

        // Group transactions by inventory_transaction_id để check tổng qty
        $inventoryQtyTotals = [];

        foreach ($transactionsData as $index => $data) {
            // Kiểm tra required fields
            if (empty($data['schedule_line_id'])) {
                $errors["transactions.{$index}.schedule_line_id"] = 'Schedule line is required';
            }

            if (empty($data['inventory_transaction_id'])) {
                $errors["transactions.{$index}.inventory_transaction_id"] = 'Lot/Batch is required';
            }

            if (empty($data['qty']) || $data['qty'] <= 0) {
                $errors["transactions.{$index}.qty"] = 'Quantity must be greater than 0';
            }

            // Gom tổng qty theo inventory_transaction_id
            if (!empty($data['inventory_transaction_id']) && !empty($data['qty'])) {
                $inventoryId = $data['inventory_transaction_id'];
                $inventoryQtyTotals[$inventoryId] = ($inventoryQtyTotals[$inventoryId] ?? 0) + $data['qty'];
            }
        }

        // Validate tổng qty theo từng inventory line
        $excludeShipmentId = $transactionsData[0]['exclude_shipment_id'] ?? null;

        foreach ($inventoryQtyTotals as $inventoryId => $totalQty) {
            $available = $this->getInventoryTransactionRemaining($inventoryId, $excludeShipmentId);

            if ($totalQty > $available) {
                // Tìm các transactions sử dụng inventory line này để báo lỗi
                foreach ($transactionsData as $index => $data) {
                    if ($data['inventory_transaction_id'] == $inventoryId) {
                        // Không query database trong validation - chỉ dùng ID
                        $errors["transactions.{$index}.qty"] = "Tổng số lượng {$totalQty} của Lot #{$inventoryId} vượt quá tồn kho {$available}";
                        break; // Chỉ báo lỗi ở transaction đầu tiên của lot này
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Lấy danh sách child transaction IDs của shipment đang edit
     * Cần exclude các child transactions này để rollback qty về parent
     * 
     * @param int $shipmentId
     * @return array
     */
    public function getShipmentTransactionIds(int $shipmentId): array
    {
        return SalesShipment::with(['transactions'])
            ->find($shipmentId)
            ?->transactions
            ?->pluck('id')
            ?->toArray() ?? [];
    }

    /**
     * Lấy options cho lot selection form với logic exclude đúng
     * Bao gồm cả lots đang được sử dụng bởi shipment hiện tại
     * 
     * @param array $productIds
     * @param int $warehouseId  
     * @param int|null $excludeShipmentId
     * @param int|null $currentTransactionId Transaction đang được chọn
     * @return array
     */
    public function getFormOptionsForLotSelection(
        array $productIds,
        int $warehouseId,
        ?int $excludeShipmentId = null,
        ?int $currentTransactionId = null
    ): array {
        // Lấy danh sách child transaction IDs cần exclude
        $excludeTransactionIds = $excludeShipmentId ? $this->getShipmentTransactionIds($excludeShipmentId) : [];

        // Lấy tất cả lots của products (không phụ thuộc vào available)
        $allLots = InventoryTransaction::query()
            ->forProducts($productIds)
            ->inWarehouse($warehouseId)
            ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Import)
            ->with(['product'])
            ->get();

        $options = [];

        foreach ($allLots as $lot) {
            // Tính remaining quantity với exclude logic
            $shippedQty = $lot->children()
                ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export)
                ->when(!empty($excludeTransactionIds), function ($query) use ($excludeTransactionIds) {
                    $query->whereNotIn('id', $excludeTransactionIds);
                })
                ->sum('qty');

            $remainingQty = $lot->qty - $shippedQty;

            // Chỉ hiển thị lots có remaining > 0 HOẶC đang được chọn
            if ($remainingQty > 0 || $lot->id == $currentTransactionId) {
                $label = $lot->lot_description . ' | Còn: ' . __number_string_converter($remainingQty);
                $options[$lot->id] = $label;
            }
        }

        return $options;
    }

    /**
     * Lấy label hiển thị cho schedule line trong form
     * 
     * @param int $scheduleLineId
     * @return string|null
     */
    public function getScheduleLineLabel(int $scheduleLineId): ?string
    {
        $scheduleLine = SalesDeliveryScheduleLine::with(['product', 'deliverySchedule'])
            ->find($scheduleLineId);

        if (!$scheduleLine) {
            return null;
        }

        return sprintf(
            '%s - %s (%s)',
            $scheduleLine->deliverySchedule->schedule_code ?? "Schedule #{$scheduleLine->sales_delivery_schedule_id}",
            $scheduleLine->product->product_name ?? 'Unknown Product',
            __number_string_converter($scheduleLine->qty) . ' ' . ($scheduleLine->product->unit ?? '')
        );
    }
}
