<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PurchaseShipment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseShipmentPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchaseShipment');
    }

    public function view(AuthUser $authUser, PurchaseShipment $purchaseShipment): bool
    {
        return $authUser->can('View:PurchaseShipment');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchaseShipment');
    }

    public function update(AuthUser $authUser, PurchaseShipment $purchaseShipment): bool
    {
        return $authUser->can('Update:PurchaseShipment');
    }

    public function updateAny(AuthUser $authUser, PurchaseShipment $purchaseShipment): bool
    {
        return $authUser->can('UpdateAny:PurchaseShipment');
    }

    public function delete(AuthUser $authUser, PurchaseShipment $purchaseShipment): bool
    {
        return $authUser->can('Delete:PurchaseShipment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchaseShipment');
    }

}