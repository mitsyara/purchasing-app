<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseShipment;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseShipmentRepositoryInterface extends BaseRepositoryInterface
{
    public function findById(int $id): ?PurchaseShipment;
    
    public function create(array $data): PurchaseShipment;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function findByOrderId(int $purchaseOrderId): Collection;
    
    public function findByStatus(string $status): Collection;
    
    public function findByContactId(int $contactId): Collection;
    
    public function updateStatus(int $id, string $status): bool;
    
    public function calculateTotalValueForOrder(int $purchaseOrderId): float;
    
    public function getPendingShipments(): Collection;
}