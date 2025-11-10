<?php

namespace App\Services\PurchaseShipment;

use App\Enums\OrderStatusEnum;
use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use App\Models\PurchaseOrder;
use App\Enums\ShipmentStatusEnum;
use App\Helpers\OrderNumberGenerator;
use App\Services\Inventory\InventoryService;
use App\Services\PurchaseOrder\PurchaseOrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

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
        app(PurchaseOrderService::class)->processOrder($order->id);

        // Đồng bộ thông tin từ order sang shipment
        $this->syncInfoFromOrder($shipmentId);

        // Tính toán lại tổng giá trị shipment
        $totalAmount = $this->calculateShipmentTotal($shipment);
        $totalContractValue = $this->calculateShipmentContractTotal($shipment);

        $shipment->update([
            'total_value' => $totalAmount,
            'total_contract_value' => $totalContractValue
        ]);
    }

    /**
     * Đồng bộ thông tin từ order sang shipment
     * - Chỉ cập nhật Nếu chưa có thông tin:
     *   company_id, supplier_id, supplier_contract_id, supplier_payment_id, currency
     */
    public function syncInfoFromOrder(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
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
     * Đánh dấu shipment đã giao hàng
     */
    public function markShipmentDelivered(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        $shipment->update(['shipment_status' => ShipmentStatusEnum::Delivered]);
    }

    /**
     * Đánh dấu shipment đã hủy
     */
    public function markShipmentCancelled(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
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
            => $line->contract_price ? $line->qty * $line->contract_price : null);
    }

    /**
     * Validate order có thể tạo shipment
     */
}
