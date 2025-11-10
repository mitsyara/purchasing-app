<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Enums\OrderStatusEnum;
use App\Helpers\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Purchase Order
 */
class PurchaseOrderService
{
    /**
     * Sync order info
     */
    public function syncOrderInfo(int $orderId): void
    {
        $order = PurchaseOrder::findOrFail($orderId);
        // Cập nhật lại tổng giá trị order
        $order->total_value = $this->calculateOrderTotal($order);
        $order->total_contract_value = $this->calculateContractValue($order);

        // Nếu có shipment rồi thì process order
        if ($order->purchaseShipments()->exists()) {
            $this->processOrder($orderId);
        }

        $this->logEditUser($orderId);

        $order->save();
    }

    /**
     * Xử lý order (chuyển trạng thái sang In Progress)
     */
    public function processOrder(int $orderId): bool
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Validate order có thể được xử lý
        if (!$order->order_status || $order->order_status === OrderStatusEnum::Draft) {
            $order->order_status = OrderStatusEnum::Inprogress;
            // Thiết lập ngày order nếu chưa có
            if (!isset($order->order_date)) {
                $order->order_date = today();
            }
            // Tạo số order nếu chưa có
            if (!isset($order->order_number) || empty($order->order_number)) {
                $orderNumber = $this->generateOrderNumber([
                    'company_id' => $order->company_id,
                    'order_date' => $order->order_date ?? today()->format('Y-m-d'),
                    'supplier_id' => $order->supplier_id,
                ]);
                $order->order_number = $orderNumber;
            }
            return $order->save();
        }

        return false;
    }

    /**
     * Hoàn thành order (chuyển trạng thái sang Completed)
     */
    public function markAsCompleted(int $orderId): bool
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Validate order có thể hủy
        if (
            $order->order_status !== OrderStatusEnum::Inprogress
            // TODO: Thêm điều kiện kiểm tra tất cả shipment của order đã được giao chưa
        ) {
            throw ValidationException::withMessages([
                'order_status' => 'Chỉ có thể hủy order ở trạng thái InProgress.'
            ]);
        }

        return $order->update([
            'order_status' => OrderStatusEnum::Completed,
        ]);
    }

    /**
     * Hủy order (chuyển trạng thái sang Canceled)
     */
    public function cancelOrder(int $orderId): bool
    {
        $order = PurchaseOrder::findOrFail($orderId);

        // Validate order có thể hoàn thành
        if ($order->order_status === OrderStatusEnum::Completed) {
            throw ValidationException::withMessages([
                'order_status' => 'Order đã được hoàn thành.'
            ]);
        }

        return $order->update([
            'order_status' => OrderStatusEnum::Canceled,
        ]);
    }

    /**
     * Tính tổng giá trị order
     */
    public function calculateOrderTotal(PurchaseOrder $order): float
    {
        return $order->purchaseOrderLines
            ->sum(fn(PurchaseOrderLine $line) => $line->qty * $line->unit_price);
    }
    /**
     * Tính tổng giá trị hợp đồng order
     */
    public function calculateContractValue(PurchaseOrder $order): float
    {
        return $order->purchaseOrderLines
            ->sum(fn(PurchaseOrderLine $line) => $line->qty * ($line->contract_price ?? $line->unit_price));
    }

    /**
     * Tạo số order tự động (PO-{companyId}{ymd}/{supplierId}.###)
     * Tìm theo prefix, pad số thứ tự 3 chữ số
     */
    public function generateOrderNumber(array $data, ?int $orderId = null): string
    {
        if (
            !isset($data['company_id']) ||
            !isset($data['order_date']) ||
            !isset($data['supplier_id'])
        ) {
            throw new \InvalidArgumentException('Thiếu thông tin để tạo số order.');
        }

        $prefix = 'PO-' . $data['company_id'];
        $date = Carbon::createFromFormat('Y-m-d', $data['order_date']);
        $date = $date->format('ymd');
        // $supplierId = str_pad($data['supplier_id'], 3, '0', STR_PAD_LEFT);
        $supplierId = $data['supplier_id'];

        $code = "{$prefix}{$date}/{$supplierId}.";

        // Tìm số thứ tự cuối cùng trong ngày
        $lastOrder = PurchaseOrder::where('order_number', 'LIKE', "{$prefix}{$date}%")
            ->when($orderId, fn($query) => $query->where('id', '!=', $orderId))
            ->orderBy('order_number', 'desc')
            ->first();

        if (!$lastOrder) {
            $sequence = 1;
        } else {
            $lastSequence = (int) substr($lastOrder->supplier_order_no, -3);
            $sequence = $lastSequence + 1;
        }

        return $code . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Log người cập nhật khi có thay đổi thông tin order
     */
    public function logEditUser(int $orderId): void
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
    }
}
