<?php

namespace App\Repositories;

use App\Models\PurchaseShipment;
use App\Repositories\Contracts\PurchaseShipmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PurchaseShipmentRepository extends BaseRepository implements PurchaseShipmentRepositoryInterface
{
    public function getModelClass(): string
    {
        return PurchaseShipment::class;
    }

    public function findById(int $id): ?PurchaseShipment
    {
        return $this->find($id);
    }

    public function create(array $data): PurchaseShipment
    {
        return parent::create($data);
    }

    public function findByOrderId(int $purchaseOrderId): Collection
    {
        return $this->model::where('purchase_order_id', $purchaseOrderId)->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model::where('shipment_status', $status)->get();
    }

    public function findByContactId(int $contactId): Collection
    {
        return $this->model::where('contact_id', $contactId)->get();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['shipment_status' => $status]);
    }

    public function calculateTotalValueForOrder(int $purchaseOrderId): float
    {
        return $this->model::where('purchase_order_id', $purchaseOrderId)
            ->sum('total_value');
    }

    public function getPendingShipments(): Collection
    {
        return $this->model::where('shipment_status', 'pending')->get();
    }
}