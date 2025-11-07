<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use App\Models\PurchaseOrder;
use App\Enums\ShipmentStatusEnum;
use App\Helpers\OrderNumberGenerator;
use App\Services\Inventory\InventoryService;
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
     * Tạo mới purchase shipment từ purchase order
     */
    public function createFromOrder(PurchaseOrder $order, array $data = []): PurchaseShipment
    {
        // Validate order có thể tạo shipment
        $this->validateOrderForShipment($order);
        
        return DB::transaction(function () use ($order, $data) {
            // Chuẩn bị data cho shipment
            $shipmentData = array_merge([
                'company_id' => $order->company_id,
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'shipment_status' => ShipmentStatusEnum::Pending,
                'created_by' => auth()->id(),
            ], $data);

            // Tạo shipment number
            if (empty($shipmentData['shipment_no']) && !empty($shipmentData['company_id'])) {
                $shipmentDate = $shipmentData['shipment_date'] ?? now()->format('Y-m-d');
                $shipmentData['shipment_no'] = OrderNumberGenerator::generateShipmentNumber(
                    $shipmentData['company_id'], 
                    $shipmentDate
                );
            }

            // Tạo shipment
            $shipment = PurchaseShipment::create($shipmentData);

            // Tạo shipment lines từ order lines
            $this->createShipmentLinesFromOrder($shipment, $order);

            return $shipment->load(['purchaseShipmentLines.product', 'purchaseOrder', 'supplier']);
        });
    }

    /**
     * Tạo shipment thủ công
     */
    public function create(array $data): PurchaseShipment
    {
        // Validate dữ liệu
        $this->validateShipmentData($data);
        
        // Set user tạo
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return DB::transaction(function () use ($data) {
            // Tách shipment lines khỏi data chính
            $shipmentLines = $data['lines'] ?? [];
            unset($data['lines']);
            
            // Tạo shipment number nếu chưa có
            if (empty($data['shipment_no']) && !empty($data['company_id'])) {
                $shipmentDate = $data['shipment_date'] ?? now()->format('Y-m-d');
                $data['shipment_no'] = OrderNumberGenerator::generateShipmentNumber(
                    $data['company_id'], 
                    $shipmentDate
                );
            }
            
            // Tạo shipment
            $shipment = PurchaseShipment::create($data);
            
            // Tạo shipment lines nếu có
            if (!empty($shipmentLines)) {
                $this->createShipmentLines($shipment, $shipmentLines);
            }
            
            return $shipment->load(['purchaseShipmentLines.product', 'purchaseOrder', 'supplier']);
        });
    }

    /**
     * Cập nhật shipment
     */
    public function update(int $id, array $data): bool
    {
        $shipment = PurchaseShipment::findOrFail($id);
        
        // Validate dữ liệu
        $this->validateShipmentData($data, $id);
        
        // Set user cập nhật
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        return DB::transaction(function () use ($shipment, $data) {
            // Tách shipment lines khỏi data chính
            $shipmentLines = $data['lines'] ?? [];
            unset($data['lines']);
            
            // Cập nhật shipment
            $result = $shipment->update($data);
            
            // Cập nhật shipment lines nếu có
            if (!empty($shipmentLines)) {
                $this->updateShipmentLines($shipment, $shipmentLines);
            }
            
            return $result;
        });
    }

    /**
     * Xác nhận shipment (chuyển sang Processing)
     */
    public function confirmShipment(int $shipmentId, array $data = []): bool
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);

        // Validate shipment có thể xác nhận
        if ($shipment->shipment_status !== ShipmentStatusEnum::Pending) {
            throw ValidationException::withMessages([
                'shipment_status' => 'Chỉ có thể xác nhận shipment ở trạng thái Pending.'
            ]);
        }

        $updateData = array_merge([
            'shipment_status' => ShipmentStatusEnum::InTransit,
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ], $data);

        return $shipment->update($updateData);
    }

    /**
     * Nhận hàng vào kho (chuyển sang Received)
     */
    public function receiveShipment(int $shipmentId, array $data = []): bool
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);

        // Validate shipment có thể nhận
        if ($shipment->shipment_status !== ShipmentStatusEnum::InTransit && $shipment->shipment_status !== ShipmentStatusEnum::Arrived) {
            throw ValidationException::withMessages([
                'shipment_status' => 'Chỉ có thể nhận hàng khi shipment đang InTransit hoặc Arrived.'
            ]);
        }

        return DB::transaction(function () use ($shipment, $data) {
            // Cập nhật trạng thái shipment
            $updateData = array_merge([
                'shipment_status' => ShipmentStatusEnum::Delivered,
                'received_at' => now(),
                'received_by' => auth()->id(),
            ], $data);

            $result = $shipment->update($updateData);

            // Tạo inventory transactions cho các shipment lines
            $this->createInventoryTransactions($shipment);

            return $result;
        });
    }

    /**
     * Hủy shipment
     */
    public function cancelShipment(int $shipmentId, ?string $reason = null): bool
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);

        // Validate shipment có thể hủy
        if ($shipment->shipment_status === ShipmentStatusEnum::Delivered) {
            throw ValidationException::withMessages([
                'shipment_status' => 'Không thể hủy shipment đã nhận hàng.'
            ]);
        }

        return $shipment->update([
            'shipment_status' => ShipmentStatusEnum::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Lấy shipments theo purchase order
     */
    public function getShipmentsByOrder(int $orderId): Collection
    {
        return PurchaseShipment::where('purchase_order_id', $orderId)
            ->with(['purchaseShipmentLines.product', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy shipments theo trạng thái
     */
    public function getShipmentsByStatus(ShipmentStatusEnum $status): Collection
    {
        return PurchaseShipment::where('shipment_status', $status)
            ->with(['purchaseOrder', 'supplier', 'purchaseShipmentLines.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tính tổng giá trị shipment
     */
    public function calculateShipmentTotal(PurchaseShipment $shipment): float
    {
        return $shipment->purchaseShipmentLines->sum(fn($line) => $line->qty * $line->unit_price);
    }

    /**
     * Validate order có thể tạo shipment
     */
    private function validateOrderForShipment(PurchaseOrder $order): void
    {
        if ($order->order_status === \App\Enums\OrderStatusEnum::Draft) {
            throw ValidationException::withMessages([
                'order' => 'Không thể tạo shipment từ order ở trạng thái Draft.'
            ]);
        }

        if ($order->order_status === \App\Enums\OrderStatusEnum::Canceled) {
            throw ValidationException::withMessages([
                'order' => 'Không thể tạo shipment từ order đã bị hủy.'
            ]);
        }
    }

    /**
     * Validate dữ liệu shipment
     */
    private function validateShipmentData(array $data, ?int $excludeId = null): void
    {
        $errors = [];

        // Kiểm tra supplier
        if (empty($data['supplier_id'])) {
            $errors['supplier_id'] = 'Nhà cung cấp là bắt buộc.';
        }

        // Kiểm tra warehouse
        if (empty($data['warehouse_id'])) {
            $errors['warehouse_id'] = 'Kho hàng là bắt buộc.';
        }

        // Kiểm tra shipment number nếu có
        if (!empty($data['shipment_no'])) {
            $query = PurchaseShipment::where('shipment_no', $data['shipment_no']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                $errors['shipment_no'] = 'Số shipment đã tồn tại.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Tạo shipment lines từ order lines
     */
    private function createShipmentLinesFromOrder(PurchaseShipment $shipment, PurchaseOrder $order): void
    {
        foreach ($order->purchaseOrderLines as $orderLine) {
            PurchaseShipmentLine::create([
                'purchase_shipment_id' => $shipment->id,
                'product_id' => $orderLine->product_id,
                'qty' => $orderLine->qty,
                'unit_price' => $orderLine->unit_price,
                'currency' => $orderLine->currency ?? 'VND',
                'purchase_order_line_id' => $orderLine->id,
            ]);
        }
    }

    /**
     * Tạo shipment lines
     */
    private function createShipmentLines(PurchaseShipment $shipment, array $lines): void
    {
        foreach ($lines as $lineData) {
            $lineData['purchase_shipment_id'] = $shipment->id;
            PurchaseShipmentLine::create($lineData);
        }
    }

    /**
     * Cập nhật shipment lines
     */
    private function updateShipmentLines(PurchaseShipment $shipment, array $lines): void
    {
        // Xóa tất cả lines cũ
        $shipment->purchaseShipmentLines()->delete();
        
        // Tạo lại lines mới
        $this->createShipmentLines($shipment, $lines);
    }

    /**
     * Tạo inventory transactions khi nhận hàng
     */
    private function createInventoryTransactions(PurchaseShipment $shipment): void
    {
        foreach ($shipment->purchaseShipmentLines as $line) {
            $this->inventoryService->createImportTransactionFromShipment($line);
        }
    }

    /**
     * Đánh dấu shipment đã giao hàng (method được gọi từ Model)
     */
    public function markShipmentDelivered(int $shipmentId): bool
    {
        return $this->receiveShipment($shipmentId, [
            'delivered_at' => now(),
            'delivered_by' => auth()->id(),
        ]);
    }

    /**
     * Sync shipment info (backward compatibility)
     */
    public function syncShipmentInfo(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        
        // Logic đồng bộ thông tin shipment
        $totalAmount = $this->calculateShipmentTotal($shipment);
        
        $shipment->update([
            'total_value' => $totalAmount,
            // 'total_contract_value'
        ]);
    }
}