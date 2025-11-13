<?php

namespace App\Services\SalesOrder;

use App\Enums\OrderStatusEnum;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Carbon\Carbon;

/**
 * Service xử lý business logic cho Sales Order
 */
class SalesOrderService
{
    // -------------------------- SALES ORDER SERVICES --------------------------

    public function syncOrderInfo(int $orderId): void
    {
        $order = SalesOrder::findOrFail($orderId);

        $this->syncOrderLines($order);

        // Cập nhật lại tổng giá trị
        $order->total_value = $this->calculateOrderTotal($order);
        $order->total_contract_value = $this->calculateContractValue($order);

        // Nếu có lịch giao hàng => process order
        if ($order->deliverySchedules()->exists()) {
            $this->processOrder($order);
        }

        // Log người cập nhật
        $this->logEditUser($order);

        $order->save();
    }

    /**
     * Sync các dòng đơn hàng (nếu cần)
     */
    public function syncOrderLines(SalesOrder $order): void
    {
        return;
    }


    /**
     * Tính tổng giá trị đơn hàng
     * - Lấy dữ liệu từ DeliveryScheduleLines (qty * unit_price)
     */
    protected function calculateOrderTotal(SalesOrder $order): float
    {
        return $order->salesOrderLines()->sum('value');
    }

    /**
     * Tính tổng giá trị hợp đồng
     * - Lấy dữ liệu từ DeliveryScheduleLines (qty * contract_price)
     */
    protected function calculateContractValue(SalesOrder $order): float
    {
        return $order->salesOrderLines()->sum('contract_value');
    }

    /**
     * Xử lý đơn hàng
     */
    protected function processOrder(SalesOrder $order): bool
    {
        // Validate order có thể xử lý được
        if (!$order->order_status || $order->order_status === OrderStatusEnum::Draft) {
            $order->order_status = OrderStatusEnum::Inprogress;

            // Tạo số order nếu chưa có
            if (!$order->order_number) {
                // Thiết lập ngày order nếu chưa có
                if (!$order->order_date) {
                    $order->order_date = today();
                }

                $order->order_number = $this->generateOrderNumber([
                    'company_id' => $order->company_id,
                    'order_date' => $order->order_date?->format('Y-m-d') ?? today()->format('Y-m-d'),
                    'customer_id' => $order->customer_id,
                ]);
            }

            return $order->save();
        }

        return false;
    }

    /**
     * Ghi nhận người chỉnh sửa đơn hàng
     */
    protected function logEditUser(SalesOrder $order): void
    {
        if ($order->wasChanged([
            'order_status',
            'order_date',
            'order_number',
            'order_description',
            'company_id',
            'customer_id',
            'customer_contract_id',
            'customer_payment_id',
            'export_warehouse_id',
            'export_port_id',
            'staff_sales_id',
            'staff_approved_id',
            'etd_min',
            'etd_max',
            'eta_min',
            'eta_max',
            'currency',
            'pay_term_delay_at',
            'pay_term_days',
            'payment_method',
            'notes',
        ])) {
            $order->updateQuietly(['updated_by' => auth()->id()]);
        }
    }

    /**
     * Tạo số đơn hàng tự động
     */
    public function generateOrderNumber(array $data, ?int $orderId = null): string
    {
        if (
            !isset($data['company_id']) ||
            !isset($data['order_date']) ||
            !isset($data['customer_id'])
        ) {
            throw new \InvalidArgumentException('Thiếu thông tin để tạo số order.');
        }

        $prefix = 'SO-' . $data['company_id'];
        $date = Carbon::createFromFormat('Y-m-d', $data['order_date'])->format('ymd');
        $partnerId = $data['customer_id'];

        // Mã cơ bản (chưa có hậu tố)
        $baseCode = "{$prefix}{$date}/{$partnerId}";

        // Kiểm tra xem mã đã tồn tại chưa
        $existingBase = SalesOrder::where('order_number', $baseCode)
            ->when($orderId, fn($query) => $query->where('id', '!=', $orderId))
            ->exists();

        if (!$existingBase) {
            // Nếu chưa tồn tại mã => dùng luôn mã này
            return $baseCode;
        }

        // Nếu đã tồn tại => tìm hậu tố lớn nhất để +1
        $lastOrder = SalesOrder::where('order_number', 'LIKE', "{$baseCode}.%")
            ->when($orderId, fn($query) => $query->where('id', '!=', $orderId))
            ->orderBy('order_number', 'desc')
            ->first();

        if (!$lastOrder) {
            $sequence = 1;
        } else {
            // Tách hậu tố sau dấu chấm cuối
            $parts = explode('.', $lastOrder->order_number);
            $lastSequence = isset($parts[1]) ? (int) $parts[1] : 0;
            $sequence = $lastSequence + 1;
        }

        return "{$baseCode}." . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
