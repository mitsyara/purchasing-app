<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Service xử lý business logic cho Purchase Order
 */
class PurchaseOrderService
{
    // -------------------------- PURCHASE ORDER SERVICES --------------------------

    /**
     * Sync order info
     */
    public function syncOrderInfo(int $orderId): void
    {
        /** @var PurchaseOrder $order */
        $order = PurchaseOrder::findOrFail($orderId);

        // Cập nhật lại tổng giá trị
        $order->total_value = $this->calculateOrderTotal($order);
        $order->total_contract_value = $this->calculateContractValue($order);

        // Cập nhật lại đơn hàng ngoại
        $order->is_foreign = $this->isForeign($order);

        // Đồng bộ thông tin đến các line của order
        $this->syncOrderLinesInfo($order);

        // Nếu có shipment => process order
        if ($order->purchaseShipments()->exists()) {
            $this->processOrder($order);
        }

        // Log người cập nhật
        $this->logEditUser($order);

        $order->save();
    }

    /**
     * Xử lý order (chuyển trạng thái sang In Progress)
     */
    public function processOrder(PurchaseOrder $order): bool
    {
        // Validate order có thể xử lý được
        if (!$order->order_status || $order->order_status === OrderStatusEnum::Draft) {
            $order->order_status = OrderStatusEnum::Inprogress;

            // Tạo số order nếu chưa có
            if (!isset($order->order_number) || empty($order->order_number)) {
                $orderNumber = $this->generateOrderNumber([
                    'company_id' => $order->company_id,
                    'order_date' => $order->order_date?->format('Y-m-d') ?? today()->format('Y-m-d'),
                    'supplier_id' => $order->supplier_id,
                ]);

                // Thiết lập ngày order nếu chưa có
                if (!isset($order->order_date)) {
                    $order->order_date = today();
                }
                $order->order_number = $orderNumber;
            }

            return $order->save();
        }

        return false;
    }

    /**
     * Kiểm tra xem order có phải là đơn hàng ngoại không
     */
    public function isForeign(PurchaseOrder $order): bool
    {
        $partnerCountryId = $order->supplierContract?->country_id ?? $order->supplier?->country_id;
        return $partnerCountryId !== $order->company->country_id;
    }

    /**
     * Hoàn thành order (chuyển trạng thái sang Completed)
     */
    public function markAsCompleted(PurchaseOrder $order): bool
    {
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
    public function cancelOrder(PurchaseOrder $order): bool
    {
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
        return $order->purchaseOrderLines()->sum('value');
    }
    /**
     * Tính tổng giá trị hợp đồng order
     */
    public function calculateContractValue(PurchaseOrder $order): ?float
    {
        return $order->purchaseOrderLines()->sum('contract_value');
    }

    /**
     * Đồng bộ thông tin đến các line của order
     */
    public function syncOrderLinesInfo(PurchaseOrder $order): void
    {
        foreach ($order->purchaseOrderLines as $line) {
            $line->update([
                'company_id' => $order->company_id,
                'warehouse_id' => $line->warehouse_id ?? $order->import_warehouse_id,
                'currency' => $order->currency,
            ]);
        }
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
        // $partnerId = str_pad($data['supplier_id'], 3, '0', STR_PAD_LEFT);
        $partnerId = $data['supplier_id'];

        $baseCode = "{$prefix}{$date}/{$partnerId}";

        // Kiểm tra xem mã cơ bản đã tồn tại chưa
        $existingBase = PurchaseOrder::where('order_number', $baseCode)
            ->when($orderId, fn($query) => $query->where('id', '!=', $orderId))
            ->exists();

        if (!$existingBase) {
            // Nếu chưa tồn tại mã cơ bản => dùng luôn mã này
            return $baseCode;
        }

        // Nếu đã tồn tại => tìm hậu tố lớn nhất để +1
        $lastOrder = PurchaseOrder::where('order_number', 'LIKE', "{$baseCode}.%")
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

        return $baseCode . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Log người cập nhật khi có thay đổi thông tin order
     */
    public function logEditUser(PurchaseOrder $order): void
    {
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
