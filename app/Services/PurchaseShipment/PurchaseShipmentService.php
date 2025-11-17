<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use App\Enums\ShipmentStatusEnum;
use App\Services\Inventory\InventoryService;
use App\Services\PurchaseOrder\PurchaseOrderService;

/**
 * Service xử lý business logic cho Purchase Shipment
 */
class PurchaseShipmentService
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * Sync shipment info
     */
    public function syncShipmentInfo(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);

        $order = $shipment->purchaseOrder;

        // Chuyển trạng thái order nếu cần
        app(PurchaseOrderService::class)->processOrder($order);

        // Đồng bộ thông tin từ order sang shipment
        $this->syncInfoFromOrder($shipment);

        // Đồng bộ thông tin đến các giao dịch con
        $this->syncShipmentLinesInfo($shipment);

        // Đồng bộ các line sang inventory
        foreach ($shipment->purchaseShipmentLines as $line) {
            $this->inventoryService->syncFromShipmentLine($line);
        }

        // Tính toán lại tổng giá trị shipment
        $totalAmount = $this->calculateShipmentTotal($shipment);
        $totalContractValue = $this->calculateShipmentContractTotal($shipment);

        $shipment->update([
            'total_value' => $totalAmount,
            'total_contract_value' => $totalContractValue
        ]);
    }

    /**
     * Đồng bộ thông tin từ order sang shipment. Chỉ cập nhật Nếu chưa có thông tin:
     *   company_id, supplier_id, supplier_contract_id, supplier_payment_id, currency
     */
    public function syncInfoFromOrder(PurchaseShipment $shipment): void
    {
        $order = $shipment->purchaseOrder;

        $fields = [
            'company_id',
            'supplier_id',
            'supplier_contract_id',
            'supplier_payment_id',
            'currency',
        ];

        foreach ($fields as $field) {
            if (empty($shipment->{$field})) {
                $shipment->{$field} = $order->{$field};
            }
        }

        $shipment->save();
    }

    /**
     * Đồng bộ thông tin đến các line của shipment
     * - Cần cập nhật các fields: company_id, currency, warehouse_id, exchange_rate từ shipment cha
     * - Cần cập nhật các fields: purchase_order_line_id, purchase_order_id từ order cha (có hỗ trợ tìm gán theo assortment)
     */
    public function syncShipmentLinesInfo(PurchaseShipment $shipment): void
    {
        $shipment->load('purchaseShipmentLines');
        if (!$shipment->purchaseShipmentLines()->exists()) return;

        // Lấy danh sách orderline
        $orderLines = $shipment->purchaseOrder->purchaseOrderLines()->get(['id', 'product_id', 'assortment_id']);

        $shipment->loadMissing('purchaseShipmentLines')
            ->purchaseShipmentLines()->update([
                'company_id' => $shipment->company_id,
                'currency' => $shipment->currency,
                'warehouse_id' => $shipment->warehouse_id,
                'exchange_rate' => $shipment->exchange_rate,
            ]);

        // Đồng bộ order_line_id, purchase_order_id
        foreach ($shipment->purchaseShipmentLines as $line) {
            $matchedOrderLine = null;
            
            // Ưu tiên 1: Tìm theo product_id trực tiếp
            $matchedOrderLine = $orderLines->firstWhere('product_id', $line->product_id);
            
            // Ưu tiên 2: Nếu không tìm thấy, tìm theo assortment (many-to-many)
            if (!$matchedOrderLine && $line->product_id) {
                // Lấy danh sách assortment_id của product này
                $productAssortmentIds = $line->product->assortments()->pluck('assortments.id')->toArray();
                
                // Tìm orderline có assortment_id trùng với bất kỳ assortment nào của product
                foreach ($productAssortmentIds as $assortmentId) {
                    $matchedOrderLine = $orderLines->firstWhere('assortment_id', $assortmentId);
                    if ($matchedOrderLine) {
                        break; // Tìm thấy rồi thì dừng
                    }
                }
            }
            
            // Cập nhật nếu tìm thấy orderline phù hợp
            if ($matchedOrderLine) {
                $line->update([
                    'purchase_order_line_id' => $matchedOrderLine->id,
                    'purchase_order_id' => $shipment->purchase_order_id,
                ]);
            }
        }
    }

    /**
     * Đánh dấu shipment đã giao hàng
     */
    public function markDelivered(PurchaseShipment $shipment): void
    {
        $shipment->update(['shipment_status' => ShipmentStatusEnum::Delivered]);
    }

    /**
     * Đánh dấu shipment đã hủy
     */
    public function markCancelled(PurchaseShipment $shipment): void
    {
        $shipment->update(['shipment_status' => ShipmentStatusEnum::Cancelled]);
    }

    /**
     * Tính tổng giá trị shipment
     */
    public function calculateShipmentTotal(PurchaseShipment $shipment): float
    {
        return $shipment->purchaseShipmentLines
            ->sum(fn(PurchaseShipmentLine $line) => $line->qty * $line->unit_price);
    }

    /**
     * Tính tổng giá trị hợp đồng shipment
     */
    public function calculateShipmentContractTotal(PurchaseShipment $shipment): ?float
    {
        return $shipment->purchaseShipmentLines
            ->sum(fn(PurchaseShipmentLine $line)
            => $line->qty * ($line->contract_price ?? $line->unit_price));
    }

    /**
     * Validate order có thể tạo shipment
     */
}
