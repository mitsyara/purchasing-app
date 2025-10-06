<?php

namespace App\Services\Payment;

use App\Enums\OrderStatusEnum;
use App\Enums\PaytermDelayAtEnum;
use App\Models\PurchaseShipment;
use Carbon\Carbon;

class PurchaseShipmentPaymentService extends BasePaymentService
{
    protected ?\App\Models\PurchaseOrder $order = null;

    public function __construct(
        public PurchaseShipment $shipment,
        ?int $userId = null
    ) {
        parent::__construct($userId);

        if ($shipment) {
            $this->order = $shipment->order;
            $this->totalValue = $shipment->purchaseShipmentLines()->sum('value');
        }
    }

    protected function getTarget(): mixed
    {
        return $this->shipment;
    }

    protected function shouldSync(): bool
    {
        $order = $this->order;
        $shipment = $this->shipment;

        $shipmentDueDate = match ($order->payterm_delay_at) {
            PaytermDelayAtEnum::ATD => $shipment->atd,
            PaytermDelayAtEnum::ATA => $shipment->ata,
            default => null,
        };

        return $order
            && in_array($order->pay_term_delay_at, [PaytermDelayAtEnum::ATA, PaytermDelayAtEnum::ATD])
            && !in_array($order->status, [OrderStatusEnum::Draft, OrderStatusEnum::Canceled])
            && $shipmentDueDate
            && $order->order_date;
    }

    protected function calculateDueDate(): ?Carbon
    {
        $shipment = $this->shipment;
        $order = $this->order;

        return match ($order->payterm_delay_at) {
            PaytermDelayAtEnum::ATD => $shipment->atd?->copy()->addDays($order->pay_term_days),
            PaytermDelayAtEnum::ATA => $shipment->ata?->copy()->addDays($order->pay_term_days),
            default => null,
        };
    }
}
