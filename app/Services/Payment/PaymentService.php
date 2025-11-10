<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentLine;
use App\Models\PurchaseOrder;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Payment
 */
class PaymentService
{
    /**
     * Tạo payment mới cho purchase order
     */
    public function createFromOrder(PurchaseOrder $order, array $data): Payment
    {
        // Validate order có thể tạo payment
        $this->validateOrderForPayment($order);
        
        return DB::transaction(function () use ($order, $data) {
            // Chuẩn bị data cho payment
            $paymentData = array_merge([
                'company_id' => $order->company_id,
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'payment_status' => PaymentStatusEnum::Pending,
                'created_by' => auth()->id(),
            ], $data);

            // Tạo payment
            $payment = Payment::create($paymentData);

            // Tạo payment lines từ order lines nếu không có lines trong data
            if (empty($data['lines'])) {
                $this->createPaymentLinesFromOrder($payment, $order);
            } else {
                $this->createPaymentLines($payment, $data['lines']);
            }

            return $payment->load(['lines', 'purchaseOrder', 'supplier']);
        });
    }

    /**
     * Tạo payment thủ công
     */
    public function create(array $data): Payment
    {
        // Validate dữ liệu
        $this->validatePaymentData($data);
        
        // Set user tạo
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return DB::transaction(function () use ($data) {
            // Tách payment lines khỏi data chính
            $paymentLines = $data['lines'] ?? [];
            unset($data['lines']);
            
            // Tạo payment
            $payment = Payment::create($data);
            
            // Tạo payment lines nếu có
            if (!empty($paymentLines)) {
                $this->createPaymentLines($payment, $paymentLines);
            }
            
            return $payment->load(['lines', 'purchaseOrder', 'supplier']);
        });
    }

    /**
     * Cập nhật payment
     */
    public function update(int $id, array $data): bool
    {
        $payment = Payment::findOrFail($id);
        
        // Validate dữ liệu
        $this->validatePaymentData($data, $id);
        
        // Set user cập nhật
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        return DB::transaction(function () use ($payment, $data) {
            // Tách payment lines khỏi data chính
            $paymentLines = $data['lines'] ?? [];
            unset($data['lines']);
            
            // Cập nhật payment
            $result = $payment->update($data);
            
            // Cập nhật payment lines nếu có
            if (!empty($paymentLines)) {
                $this->updatePaymentLines($payment, $paymentLines);
            }
            
            return $result;
        });
    }

    /**
     * Xác nhận thanh toán
     */
    public function confirmPayment(int $paymentId, array $data = []): bool
    {
        $payment = Payment::findOrFail($paymentId);

        // Validate payment có thể xác nhận
        if ($payment->payment_status !== PaymentStatusEnum::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể xác nhận payment ở trạng thái Pending.'
            ]);
        }

        $updateData = array_merge([
            'payment_status' => PaymentStatusEnum::PartiallyPaid,
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ], $data);

        return $payment->update($updateData);
    }

    /**
     * Hoàn thành thanh toán
     */
    public function completePayment(int $paymentId, array $data = []): bool
    {
        $payment = Payment::findOrFail($paymentId);

        // Validate payment có thể hoàn thành
        if ($payment->payment_status !== PaymentStatusEnum::PartiallyPaid) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể hoàn thành payment đã được xác nhận.'
            ]);
        }

        $updateData = array_merge([
            'payment_status' => PaymentStatusEnum::Paid,
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ], $data);

        return $payment->update($updateData);
    }

    /**
     * Hủy thanh toán
     */
    public function cancelPayment(int $paymentId, ?string $reason = null): bool
    {
        $payment = Payment::findOrFail($paymentId);

        // Validate payment có thể hủy
        if ($payment->payment_status === PaymentStatusEnum::Paid) {
            throw ValidationException::withMessages([
                'status' => 'Không thể hủy payment đã hoàn thành.'
            ]);
        }

        return $payment->update([
            'payment_status' => PaymentStatusEnum::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Lấy payments theo purchase order
     */
    public function getPaymentsByOrder(int $orderId): Collection
    {
        return Payment::where('purchase_order_id', $orderId)
            ->with(['lines', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy payments theo trạng thái
     */
    public function getPaymentsByStatus(PaymentStatusEnum $status): Collection
    {
        return Payment::where('payment_status', $status)
            ->with(['purchaseOrder', 'supplier', 'lines'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tính tổng giá trị payment
     */
    public function calculatePaymentTotal(Payment $payment): float
    {
        return $payment->lines->sum('amount');
    }

    /**
     * Validate order có thể tạo payment
     */
    private function validateOrderForPayment(PurchaseOrder $order): void
    {
        if ($order->status === \App\Enums\OrderStatusEnum::Draft) {
            throw ValidationException::withMessages([
                'order' => 'Không thể tạo payment từ order ở trạng thái Draft.'
            ]);
        }

        if ($order->status === \App\Enums\OrderStatusEnum::Canceled) {
            throw ValidationException::withMessages([
                'order' => 'Không thể tạo payment từ order đã bị hủy.'
            ]);
        }
    }

    /**
     * Validate dữ liệu payment
     */
    private function validatePaymentData(array $data, ?int $excludeId = null): void
    {
        $errors = [];

        // Kiểm tra supplier
        if (empty($data['supplier_id'])) {
            $errors['supplier_id'] = 'Nhà cung cấp là bắt buộc.';
        }

        // Kiểm tra số tiền
        if (!empty($data['total_amount']) && $data['total_amount'] <= 0) {
            $errors['total_amount'] = 'Số tiền phải lớn hơn 0.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Tạo payment lines từ order lines
     */
    private function createPaymentLinesFromOrder(Payment $payment, PurchaseOrder $order): void
    {
        foreach ($order->lines as $orderLine) {
            PaymentLine::create([
                'payment_id' => $payment->id,
                'product_id' => $orderLine->product_id,
                'qty' => $orderLine->qty,
                'unit_price' => $orderLine->unit_price,
                'amount' => $orderLine->qty * $orderLine->unit_price,
                'currency' => $orderLine->currency ?? 'VND',
                'purchase_order_line_id' => $orderLine->id,
            ]);
        }
    }

    /**
     * Tạo payment lines
     */
    private function createPaymentLines(Payment $payment, array $lines): void
    {
        foreach ($lines as $lineData) {
            $lineData['payment_id'] = $payment->id;
            PaymentLine::create($lineData);
        }
    }

    /**
     * Cập nhật payment lines
     */
    private function updatePaymentLines(Payment $payment, array $lines): void
    {
        // Xóa tất cả lines cũ
        $payment->lines()->delete();
        
        // Tạo lại lines mới
        $this->createPaymentLines($payment, $lines);
    }
}