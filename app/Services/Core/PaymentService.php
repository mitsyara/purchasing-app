<?php

namespace App\Services\Core;

use App\Models\PurchaseOrder;
use App\Models\PurchaseShipment;
use App\Enums\PaymentStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaytermDelayAtEnum;
use Carbon\Carbon;

class PaymentService
{
    /**
     * Sync payment for purchase order
     */
    public function syncPurchaseOrderPayment(PurchaseOrder $order, ?int $userId = null): void
    {
        if (!$this->shouldSyncOrderPayment($order)) {
            return;
        }

        $totalValue = $order->purchaseOrderLines()->sum('value');

        $paymentData = $this->getOrderPaymentData($order, $totalValue, $userId);

        if ($order->payment) {
            $order->payment->update($paymentData);
        } else {
            $order->payment()->create(array_merge($paymentData, [
                'company_id' => $order->company_id,
                'payment_type' => 0, // 0: out, 1: in
                'payment_status' => PaymentStatusEnum::Pending,
                'created_by' => $order->created_by,
            ]));
        }
    }

    /**
     * Sync payment for purchase shipment
     */
    public function syncPurchaseShipmentPayment(PurchaseShipment $shipment, ?int $userId = null): void
    {
        if (!$this->shouldSyncShipmentPayment($shipment)) {
            return;
        }

        $totalValue = $shipment->purchaseShipmentLines()->sum('value');

        $paymentData = $this->getShipmentPaymentData($shipment, $totalValue, $userId);

        if ($shipment->payment) {
            $shipment->payment->update($paymentData);
        } else {
            $shipment->payment()->create(array_merge($paymentData, [
                'company_id' => $shipment->company_id,
                'payment_type' => 0,
                'payment_status' => PaymentStatusEnum::Pending,
                'created_by' => $shipment->created_by,
            ]));
        }
    }

    /**
     * Check if should sync order payment
     */
    protected function shouldSyncOrderPayment(PurchaseOrder $order): bool
    {
        return $order
            && $order->pay_term_delay_at === PaytermDelayAtEnum::OrderDate
            && !in_array($order->order_status, [OrderStatusEnum::Draft, OrderStatusEnum::Canceled])
            && $order->order_date;
    }

    /**
     * Check if should sync shipment payment
     */
    protected function shouldSyncShipmentPayment(PurchaseShipment $shipment): bool
    {
        // Add shipment payment sync logic here
        return $shipment && $shipment->shipment_date;
    }

    /**
     * Get payment data for order
     */
    protected function getOrderPaymentData(PurchaseOrder $order, float $totalValue, ?int $userId = null): array
    {
        return [
            'supplier_id' => $order->supplier_id,
            'supplier_contract_id' => $order->supplier_contract_id,
            'supplier_payment_id' => $order->supplier_payment_id,
            'currency' => $order->currency,
            'total_amount' => $totalValue,
            'due_date' => $this->calculateOrderDueDate($order),
            'updated_by' => $userId ?? auth()->id(),
        ];
    }

    /**
     * Get payment data for shipment
     */
    protected function getShipmentPaymentData(PurchaseShipment $shipment, float $totalValue, ?int $userId = null): array
    {
        return [
            'supplier_id' => $shipment->supplier_id,
            'supplier_contract_id' => $shipment->supplier_contract_id,
            'supplier_payment_id' => $shipment->supplier_payment_id,
            'currency' => $shipment->currency,
            'total_amount' => $totalValue,
            'due_date' => $this->calculateShipmentDueDate($shipment),
            'updated_by' => $userId ?? auth()->id(),
        ];
    }

    /**
     * Calculate due date for order
     */
    protected function calculateOrderDueDate(PurchaseOrder $order): ?Carbon
    {
        if (!$order->order_date) {
            return null;
        }

        $days = $order->pay_term_days ?? 0;

        return match ($order->pay_term_delay_at) {
            PaytermDelayAtEnum::OrderDate => $order->order_date->copy()->addDays($days),
            default => null,
        };
    }

    /**
     * Calculate due date for shipment
     */
    protected function calculateShipmentDueDate(PurchaseShipment $shipment): ?Carbon
    {
        if (!$shipment->shipment_date) {
            return null;
        }

        // Add shipment due date calculation logic
        $days = $shipment->pay_term_days ?? 0;
        return $shipment->shipment_date->copy()->addDays($days);
    }

    /**
     * Get pending payments
     */
    public function getPendingPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Payment::where('payment_status', PaymentStatusEnum::Pending)->get();
    }

    /**
     * Get overdue payments
     */
    public function getOverduePayments(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Payment::where('payment_status', PaymentStatusEnum::Pending)
            ->where('due_date', '<', now())
            ->get();
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(int $paymentId, array $data = []): bool
    {
        $payment = \App\Models\Payment::findOrFail($paymentId);
        
        return $payment->update(array_merge([
            'payment_status' => PaymentStatusEnum::Paid,
            'paid_date' => now(),
            'updated_by' => auth()->id(),
        ], $data));
    }

    /**
     * Calculate total payable amount for order
     */
    public function calculateOrderPayableAmount(PurchaseOrder $order): float
    {
        return $order->purchaseOrderLines()->sum('value') + ($order->total_extra_cost ?? 0);
    }

    /**
     * Calculate total received amount for order
     */
    public function calculateOrderReceivedAmount(PurchaseOrder $order): float
    {
        return $order->payments()->where('payment_status', PaymentStatusEnum::Paid)->sum('total_amount');
    }
}