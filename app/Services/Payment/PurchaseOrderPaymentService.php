<?php

namespace App\Services\Payment;

use App\Enums\OrderStatusEnum;
use App\Enums\PaytermDelayAtEnum;
use App\Models\PurchaseOrder;
use Carbon\Carbon;

class PurchaseOrderPaymentService extends BasePaymentService
{
    public function __construct(
        public PurchaseOrder $order,
        ?int $userId = null
    ) {
        parent::__construct($userId);

        if ($order) {
            $this->totalValue = $order->purchaseOrderLines()->sum('value');
        }
    }

    protected function getTarget(): mixed
    {
        return $this->order;
    }

    protected function shouldSync(): bool
    {
        $order = $this->order;

        return $order
            && $order->pay_term_delay_at === PaytermDelayAtEnum::OrderDate
            && !in_array($order->status, [OrderStatusEnum::Draft, OrderStatusEnum::Canceled])
            && $order->order_date;
    }

    protected function calculateDueDate(): ?Carbon
    {
        $order = $this->order;
        if (!$order->order_date) {
            return null;
        }

        $days = $order->pay_term_days ?? 0;

        return match ($order->pay_term_delay_at) {
            PaytermDelayAtEnum::OrderDate => $order->order_date->copy()->addDays($days),
            default => null,
        };
    }
}
