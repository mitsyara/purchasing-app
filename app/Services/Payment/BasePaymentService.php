<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatusEnum;
use Carbon\Carbon;

abstract class BasePaymentService
{
    protected int|float|null $totalValue = null;

    public function __construct(
        protected ?int $userId = null
    ) {}

    abstract protected function getTarget(): mixed; // PurchaseOrder hoặc PurchaseShipment
    abstract protected function calculateDueDate(): ?Carbon;

    public function syncPayment(): void
    {
        $target = $this->getTarget();

        if (!$target) {
            return;
        }

        if ($this->shouldSync()) {
            $target->payment
                ? $this->updatePayment()
                : $this->createPayment();
        }
    }

    protected function createPayment(): void
    {
        $this->getTarget()->payment()->create($this->getPaymentData(true));
    }

    protected function updatePayment(): void
    {
        $this->getTarget()->payment()->update($this->getPaymentData());
    }

    protected function getPaymentData(bool $isCreate = false): array
    {
        $target = $this->getTarget();

        $baseData = [
            'supplier_id'          => $target->supplier_id,
            'supplier_contract_id' => $target->supplier_contract_id,
            'supplier_payment_id'  => $target->supplier_payment_id,
            'currency'             => $target->currency,
            'total_amount'         => $this->totalValue,
            'due_date'             => $this->calculateDueDate(),
        ];

        if ($isCreate) {
            return array_merge($baseData, [
                'company_id'     => $target->company_id,
                'payment_type'   => 0, // 0: out, 1: in
                'payment_status' => PaymentStatusEnum::Pending,
                'created_by'     => $target->created_by,
            ]);
        }

        return array_merge($baseData, [
            'updated_by' => $this->userId ?? auth()->id(),
        ]);
    }

    /**
     * Mỗi subclass sẽ override để định nghĩa khi nào sync payment
     */
    abstract protected function shouldSync(): bool;
}
