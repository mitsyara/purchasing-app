<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class ProcessingOrder
{
    public function __construct(public PurchaseOrder $order, public array $data)
    {
        $this->validateData($data);
    }

    public function handle(): bool
    {
        // Update Order's description
        $supplierCode = $this->order->supplier->contact_short_name ?? $this->order->supplier->contact_code ?? 'N/A';
        return $this->order->updateQuietly([
            'order_status' => \App\Enums\OrderStatusEnum::Inprogress,
            'order_number' => $this->data['order_number'],
            'order_date' => $this->data['order_date'],
            'order_description' => $this->data['order_date'] . ' ' . $this->data['order_number'] . ' [' . $supplierCode . ']',
        ]);
    }

    public function validateData(array $data, ?string $format = 'Y-m-d'): void
    {
        if (!$data['order_number'] || !$data['order_date']) {
            throw new \Exception('Order number and order date are required.');
        }

        $date = \Carbon\Carbon::createFromFormat($format, $data['order_date']);

        if (!$date || $date->format($format) !== $data['order_date']) {
            throw new \Exception('Invalid order date format. Expected format: ' . $format);
        }
    }
}
