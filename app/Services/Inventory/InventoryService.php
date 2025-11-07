<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use App\Models\PurchaseShipmentLine;
use App\Enums\InventoryTransactionTypeEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Inventory
 */
class InventoryService
{
    /**
     * Đồng bộ thông tin đến các giao dịch con
     */
    public function syncInfoToDescendants(InventoryTransaction $transaction): void
    {
        if (!$transaction->descendants()->exists()) {
            return;
        }

        $transaction->descendants()->update([
            'company_id' => $transaction->company_id,
            'warehouse_id' => $transaction->warehouse_id,
            'product_id' => $transaction->product_id,
            'lot_no' => $transaction->lot_no,
            'mfg_date' => $transaction->mfg_date,
            'exp_date' => $transaction->exp_date,
            'break_price' => $transaction->break_price,
        ]);
    }

    /**
     * Đồng bộ dữ liệu giao dịch từ shipment line
     */
    public function syncFromShipmentLine(PurchaseShipmentLine $shipmentLine): void
    {
        $shipment = $shipmentLine->purchaseShipment;

        $shipmentLine->transactions()->update([
            'company_id' => $shipment->company_id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $shipmentLine->product_id,
            'transaction_type' => InventoryTransactionTypeEnum::Import->value,
            'io_price' => $shipmentLine->unit_price,
            'io_currency' => $shipmentLine->currency,
            'break_price' => $shipmentLine->break_price,
        ]);
    }

    /**
     * Tạo giao dịch inventory
     */
    public function createTransaction(array $data): InventoryTransaction
    {
        return InventoryTransaction::create($data);
    }

    /**
     * Cập nhật giao dịch inventory
     */
    public function updateTransaction(int $transactionId, array $data): bool
    {
        $transaction = InventoryTransaction::findOrFail($transactionId);
        return $transaction->update($data);
    }

    /**
     * Lấy giao dịch theo sản phẩm
     */
    public function getTransactionsByProduct(int $productId): Collection
    {
        return InventoryTransaction::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy giao dịch theo kho
     */
    public function getTransactionsByWarehouse(int $warehouseId): Collection
    {
        return InventoryTransaction::where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tính mức tồn kho hiện tại
     */
    public function calculateStockLevel(int $productId, int $warehouseId): float
    {
        $imports = InventoryTransaction::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', InventoryTransactionTypeEnum::Import)
            ->sum('qty');

        $exports = InventoryTransaction::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', InventoryTransactionTypeEnum::Export)
            ->sum('qty');

        return $imports - $exports;
    }

    /**
     * Lấy sản phẩm tồn kho thấp
     */
    public function getLowStockProducts(int $warehouseId, float $threshold = 10): Collection
    {
        return DB::table('inventory_transactions as it')
            ->join('products as p', 'it.product_id', '=', 'p.id')
            ->where('it.warehouse_id', $warehouseId)
            ->select([
                'p.id',
                'p.product_name',
                'p.sku',
                DB::raw('SUM(CASE WHEN it.transaction_type = "' . InventoryTransactionTypeEnum::Import->value . '" THEN it.qty ELSE 0 END) -
                         SUM(CASE WHEN it.transaction_type = "' . InventoryTransactionTypeEnum::Export->value . '" THEN it.qty ELSE 0 END) as current_stock')
            ])
            ->groupBy(['p.id', 'p.product_name', 'p.sku'])
            ->havingRaw('current_stock < ?', [$threshold])
            ->orderBy('current_stock', 'asc')
            ->get();
    }

    /**
     * Lấy báo cáo tồn kho theo kho
     */
    public function getStockReport(int $warehouseId): Collection
    {
        return DB::table('inventory_transactions as it')
            ->join('products as p', 'it.product_id', '=', 'p.id')
            ->where('it.warehouse_id', $warehouseId)
            ->select([
                'p.id',
                'p.product_name',
                'p.sku',
                DB::raw('SUM(CASE WHEN it.transaction_type = "' . InventoryTransactionTypeEnum::Import->value . '" THEN it.qty ELSE 0 END) as total_import'),
                DB::raw('SUM(CASE WHEN it.transaction_type = "' . InventoryTransactionTypeEnum::Export->value . '" THEN it.qty ELSE 0 END) as total_export'),
                DB::raw('SUM(CASE WHEN it.transaction_type = "' . InventoryTransactionTypeEnum::Import->value . '" THEN it.qty ELSE 0 END) -
                         SUM(CASE WHEN it.transaction_type = "' . InventoryTransactionTypeEnum::Export->value . '" THEN it.qty ELSE 0 END) as current_stock')
            ])
            ->groupBy(['p.id', 'p.product_name', 'p.sku'])
            ->orderBy('p.product_name')
            ->get();
    }

    /**
     * Tạo giao dịch nhập kho từ shipment line
     */
    public function createImportTransactionFromShipment(PurchaseShipmentLine $shipmentLine): InventoryTransaction
    {
        $shipment = $shipmentLine->purchaseShipment;

        return $this->createTransaction([
            'company_id' => $shipment->company_id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $shipmentLine->product_id,
            'transaction_type' => InventoryTransactionTypeEnum::Import,
            'qty' => $shipmentLine->qty,
            'io_price' => $shipmentLine->unit_price,
            'io_currency' => $shipmentLine->currency ?? 'VND',
            'break_price' => $shipmentLine->break_price,
            'lot_no' => $shipmentLine->lot_no,
            'mfg_date' => $shipmentLine->mfg_date,
            'exp_date' => $shipmentLine->exp_date,
            'reference_type' => 'purchase_shipment_line',
            'reference_id' => $shipmentLine->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Tạo giao dịch xuất kho
     */
    public function createExportTransaction(array $data): InventoryTransaction
    {
        // Kiểm tra tồn kho trước khi xuất
        $currentStock = $this->calculateStockLevel($data['product_id'], $data['warehouse_id']);
        
        if ($currentStock < $data['qty']) {
            throw new \Exception("Không đủ tồn kho. Tồn kho hiện tại: {$currentStock}");
        }

        $data['transaction_type'] = InventoryTransactionTypeEnum::Export;
        $data['created_by'] = auth()->id();

        return $this->createTransaction($data);
    }

    /**
     * Điều chuyển hàng giữa các kho
     */
    public function transferStock(int $productId, int $fromWarehouseId, int $toWarehouseId, float $qty, array $additionalData = []): array
    {
        // Kiểm tra tồn kho tại kho xuất
        $currentStock = $this->calculateStockLevel($productId, $fromWarehouseId);
        
        if ($currentStock < $qty) {
            throw new \Exception("Không đủ tồn kho tại kho xuất. Tồn kho hiện tại: {$currentStock}");
        }

        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $qty, $additionalData) {
            // Tạo giao dịch xuất kho
            $exportTransaction = $this->createTransaction(array_merge([
                'product_id' => $productId,
                'warehouse_id' => $fromWarehouseId,
                'transaction_type' => InventoryTransactionTypeEnum::Export,
                'qty' => $qty,
                'reference_type' => 'stock_transfer',
                'created_by' => auth()->id(),
            ], $additionalData));

            // Tạo giao dịch nhập kho
            $importTransaction = $this->createTransaction(array_merge([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'transaction_type' => InventoryTransactionTypeEnum::Import,
                'qty' => $qty,
                'reference_type' => 'stock_transfer',
                'reference_id' => $exportTransaction->id,
                'created_by' => auth()->id(),
            ], $additionalData));

            return [
                'export_transaction' => $exportTransaction,
                'import_transaction' => $importTransaction,
            ];
        });
    }
}