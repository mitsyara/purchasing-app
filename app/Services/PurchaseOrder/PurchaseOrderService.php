<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Enums\OrderStatusEnum;
use App\Helpers\OrderNumberGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Purchase Order
 */
class PurchaseOrderService
{
    /**
     * Tạo mới purchase order
     */
    public function create(array $data): PurchaseOrder
    {
        // Validate dữ liệu
        $this->validateOrderData($data);
        
        // Set user tạo nếu đã đăng nhập
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        // Tạo order number nếu chưa có
        if (empty($data['order_no']) && !empty($data['company_id'])) {
            $orderDate = $data['order_date'] ?? now()->format('Y-m-d');
            $data['order_no'] = OrderNumberGenerator::generatePurchaseOrderNumber($data['company_id'], $orderDate);
        }

        return DB::transaction(function () use ($data) {
            // Tách order lines khỏi data chính
            $orderLines = $data['lines'] ?? [];
            unset($data['lines']);
            
            // Tạo purchase order
            $purchaseOrder = PurchaseOrder::create($data);
            
            // Tạo order lines nếu có
            if (!empty($orderLines)) {
                $this->createOrderLines($purchaseOrder, $orderLines);
            }
            
            return $purchaseOrder;
        });
    }

    /**
     * Cập nhật purchase order
     */
    public function update(int $id, array $data): bool
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        
        // Validate dữ liệu
        $this->validateOrderData($data, $id);
        
        // Set user cập nhật
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        return DB::transaction(function () use ($purchaseOrder, $data) {
            // Tách order lines khỏi data chính
            $orderLines = $data['lines'] ?? [];
            unset($data['lines']);
            
            // Cập nhật purchase order
            $result = $purchaseOrder->update($data);
            
            // Cập nhật order lines nếu có
            if (!empty($orderLines)) {
                $this->updateOrderLines($purchaseOrder, $orderLines);
            }
            
            return $result;
        });
    }

    /**
     * Xử lý order (chuyển trạng thái sang In Progress)
     */
    public function processOrder(int $orderId, array $data = []): bool
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Validate order có thể được xử lý
        if ($order->order_status !== OrderStatusEnum::Draft) {
            throw ValidationException::withMessages([
                'order_status' => 'Chỉ có thể xử lý order ở trạng thái Draft.'
            ]);
        }

        return DB::transaction(function () use ($order, $data) {
            // Cập nhật supplier code và status
            $updateData = [
                'order_status' => OrderStatusEnum::Inprogress,
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ];

            // Tạo supplier code nếu chưa có
            if (empty($order->supplier_order_no) && $order->supplier) {
                $supplierCode = $order->supplier->contact_short_name ?? 'SUP';
                $updateData['supplier_order_no'] = $this->generateSupplierOrderNumber($supplierCode);
            }

            // Merge với data bổ sung
            $updateData = array_merge($updateData, $data);

            return $order->update($updateData);
        });
    }

    /**
     * Hoàn thành order
     */
    public function completeOrder(int $orderId): bool
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Validate order có thể hủy
        if ($order->order_status !== OrderStatusEnum::Inprogress) {
            throw ValidationException::withMessages([
                'order_status' => 'Chỉ có thể hủy order ở trạng thái InProgress.'
            ]);
        }

        return $order->update([
            'order_status' => OrderStatusEnum::Canceled,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);
    }

    /**
     * Hủy order
     */
    public function cancelOrder(int $orderId, ?string $reason = null): bool
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Validate order có thể hoàn thành
        if ($order->order_status === OrderStatusEnum::Completed) {
            throw ValidationException::withMessages([
                'order_status' => 'Order đã được hoàn thành.'
            ]);
        }

        return $order->update([
            'order_status' => OrderStatusEnum::Completed,
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Lấy orders theo trạng thái
     */
    public function getOrdersByStatus(OrderStatusEnum $status): Collection
    {
        return PurchaseOrder::where('order_status', $status)
            ->with(['supplier', 'purchaseOrderLines.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tìm kiếm orders
     */
    public function search(array $criteria): Collection
    {
        $query = PurchaseOrder::with(['supplier', 'purchaseOrderLines.product']);

        if (!empty($criteria['order_no'])) {
            $query->where('order_no', 'LIKE', '%' . $criteria['order_no'] . '%');
        }

        if (!empty($criteria['supplier_id'])) {
            $query->where('supplier_id', $criteria['supplier_id']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['date_from'])) {
            $query->whereDate('created_at', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->whereDate('created_at', '<=', $criteria['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Tính tổng giá trị order
     */
    public function calculateOrderTotal(PurchaseOrder $order): float
    {
        return $order->purchaseOrderLines->sum(fn($line) => $line->qty * $line->unit_price);
    }

    /**
     * Tạo order lines
     */
    private function createOrderLines(PurchaseOrder $purchaseOrder, array $lines): void
    {
        foreach ($lines as $lineData) {
            $lineData['purchase_order_id'] = $purchaseOrder->id;
            PurchaseOrderLine::create($lineData);
        }
    }

    /**
     * Cập nhật order lines
     */
    private function updateOrderLines(PurchaseOrder $purchaseOrder, array $lines): void
    {
        // Xóa tất cả lines cũ
        $purchaseOrder->purchaseOrderLines()->delete();
        
        // Tạo lại lines mới
        $this->createOrderLines($purchaseOrder, $lines);
    }

    /**
     * Validate dữ liệu order
     */
    private function validateOrderData(array $data, ?int $excludeId = null): void
    {
        $errors = [];

        // Kiểm tra supplier
        if (empty($data['supplier_id'])) {
            $errors['supplier_id'] = 'Nhà cung cấp là bắt buộc.';
        }

        // Kiểm tra order number nếu có
        if (!empty($data['order_no'])) {
            $query = PurchaseOrder::where('order_no', $data['order_no']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                $errors['order_no'] = 'Số order đã tồn tại.';
            }
        }

        // Kiểm tra ngày order
        if (!empty($data['order_date'])) {
            if (strtotime($data['order_date']) === false) {
                $errors['order_date'] = 'Ngày order không đúng định dạng.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Tạo supplier order number
     */
    private function generateSupplierOrderNumber(string $supplierCode): string
    {
        $prefix = strtoupper($supplierCode);
        $date = now()->format('Ymd');
        
        // Tìm số thứ tự cuối cùng trong ngày
        $lastOrder = PurchaseOrder::where('supplier_order_no', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('supplier_order_no', 'desc')
            ->first();

        if (!$lastOrder) {
            $sequence = 1;
        } else {
            $lastSequence = (int) substr($lastOrder->supplier_order_no, -3);
            $sequence = $lastSequence + 1;
        }

        return $prefix . $date . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Đồng bộ thông tin order (method được gọi từ Model)
     */
    public function syncOrderInfo(int $orderId): void
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Log the user who updated the record
        if ($order->wasChanged([
            'order_status',
            'order_date',
            'order_number',
            'company_id',
            'supplier_id',
            'supplier_contract_id',
            'import_warehouse_id',
            'import_port_id',
            'staff_buy_id',
            'staff_approved_id',
            'staff_docs_id',
            'staff_declarant_id',
            'staff_sales_id',
            'etd_min',
            'etd_max',
            'eta_min',
            'eta_max',
            'is_skip_invoice',
            'incoterm',
            'currency',
            'pay_term_delay_at',
            'pay_term_days',
            'notes',
        ])) {
            $order->updateQuietly(['updated_by' => auth()->id()]);
        }
        
        // Logic đồng bộ thông tin order nếu cần
        // Ví dụ: cập nhật tổng tiền, trạng thái, etc.
        $totalAmount = $order->purchaseOrderLines->sum(fn($line) => $line->qty * $line->unit_price);
        
        $order->update([
            'total_value' => $totalAmount,
        ]);
    }

    /**
     * Tạo số order tự động
     */
    public function generateOrderNumber(array $data, ?int $excludeId = null): string
    {
        if (!empty($data['company_id'])) {
            $orderDate = $data['order_date'] ?? now()->format('Y-m-d');
            return OrderNumberGenerator::generatePurchaseOrderNumber($data['company_id'], $orderDate);
        }
        
        return 'PO-' . now()->format('ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Cập nhật tổng tiền cho order
     */
    public function updateTotals(int $orderId): void
    {
        $this->syncOrderInfo($orderId);
    }

    /**
     * Sync order lines info (backward compatibility)
     */
    public function syncOrderLinesInfo(int $orderId): void
    {
        $this->syncOrderInfo($orderId);
    }

    /**
     * Update order info (backward compatibility) 
     */
    public function updateOrderInfo(int $orderId): void
    {
        $this->syncOrderInfo($orderId);
    }


}