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
        app(PurchaseOrderService::class)->processOrder($order);

        // Đồng bộ thông tin từ order sang shipment
        $this->syncInfoFromOrder($shipment);

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
