<?php

namespace App\Services\Core;

use App\Models\InventoryTransaction;
use App\Models\PurchaseShipmentLine;

class InventoryService
{
    /**
     * Sync information to descendants transactions
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
     * Sync transaction data from shipment line
     */
    public function syncFromShipmentLine(PurchaseShipmentLine $shipmentLine): void
    {
        $shipment = $shipmentLine->purchaseShipment;

        $shipmentLine->transactions()->update([
            'company_id' => $shipment->company_id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $shipmentLine->product_id,
            'transaction_type' => \App\Enums\InventoryTransactionTypeEnum::Import->value,
            'io_price' => $shipmentLine->unit_price,
            'io_currency' => $shipmentLine->currency,
            'break_price' => $shipmentLine->break_price,
        ]);
    }

    /**
     * Create inventory transaction
     */
    public function createTransaction(array $data): InventoryTransaction
    {
        return InventoryTransaction::create($data);
    }

    /**
     * Update inventory transaction
     */
    public function updateTransaction(int $transactionId, array $data): bool
    {
        $transaction = InventoryTransaction::findOrFail($transactionId);
        return $transaction->update($data);
    }

    /**
     * Get transactions by product
     */
    public function getTransactionsByProduct(int $productId): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryTransaction::where('product_id', $productId)->get();
    }

    /**
     * Get transactions by warehouse
     */
    public function getTransactionsByWarehouse(int $warehouseId): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryTransaction::where('warehouse_id', $warehouseId)->get();
    }

    /**
     * Calculate current stock level
     */
    public function calculateStockLevel(int $productId, int $warehouseId): float
    {
        $imports = InventoryTransaction::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', \App\Enums\InventoryTransactionTypeEnum::Import)
            ->sum('quantity');

        $exports = InventoryTransaction::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', \App\Enums\InventoryTransactionTypeEnum::Export)
            ->sum('quantity');

        return $imports - $exports;
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(int $warehouseId, float $threshold = 10): array
    {
        $products = \App\Models\Product::all();
        $lowStock = [];

        foreach ($products as $product) {
            $currentStock = $this->calculateStockLevel($product->id, $warehouseId);
            if ($currentStock <= $threshold) {
                $lowStock[] = [
                    'product' => $product,
                    'current_stock' => $currentStock,
                ];
            }
        }

        return $lowStock;
    }

    /**
     * Get transactions by date range
     */
    public function getTransactionsByDateRange(string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryTransaction::whereBetween('created_at', [$startDate, $endDate])->get();
    }

    /**
     * Get transaction history for product
     */
    public function getProductHistory(int $productId, ?int $warehouseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = InventoryTransaction::where('product_id', $productId);
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Validate transaction data
     */
    public function validateTransaction(array $data): array
    {
        $errors = [];

        if (empty($data['product_id'])) {
            $errors['product_id'] = 'Product is required';
        }

        if (empty($data['warehouse_id'])) {
            $errors['warehouse_id'] = 'Warehouse is required';
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            $errors['quantity'] = 'Quantity must be greater than 0';
        }

        if (empty($data['transaction_type'])) {
            $errors['transaction_type'] = 'Transaction type is required';
        }

        return $errors;
    }

    /**
     * Process batch transactions
     */
    public function processBatchTransactions(array $transactions): array
    {
        $results = [];
        
        foreach ($transactions as $transactionData) {
            try {
                $errors = $this->validateTransaction($transactionData);
                if (!empty($errors)) {
                    $results[] = ['success' => false, 'errors' => $errors, 'data' => $transactionData];
                    continue;
                }

                $transaction = $this->createTransaction($transactionData);
                $results[] = ['success' => true, 'transaction' => $transaction];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage(), 'data' => $transactionData];
            }
        }

        return $results;
    }
}