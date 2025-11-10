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

        // Logic đồng bộ thông tin shipment
        $shipment->update([
            'company_id' => $order->company_id,
            'port_id' => $order->import_port_id,
            'warehouse_id' => $order->import_warehouse_id,

            'supplier_id' => $order->supplier_id,
            'supplier_contract_id' => $order->supplier_contract_id,
            'supplier_payment_id' => $order->supplier_payment_id,

            'currency' => $order->currency,
        ]);

        // Tính toán lại tổng giá trị shipment
        $totalAmount = $this->calculateShipmentTotal($shipment);
        $totalContractValue = $this->calculateShipmentContractTotal($shipment);

        $shipment->update([
            'total_value' => $totalAmount,
            'total_contract_value' => $totalContractValue
        ]);
    }

    public function markShipmentDelivered(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        $shipment->update(['status' => ShipmentStatusEnum::Delivered]);
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
    public function calculateShipmentContractTotal(PurchaseShipment $shipment): float
    {
        return $shipment->purchaseShipmentLines
            ->sum(fn(PurchaseShipmentLine $line) => $line->qty * ($line->contract_price ?? $line->unit_price));
    }

    /**
     * Validate order có thể tạo shipment
     */
}
