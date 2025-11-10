<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PurchaseOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchaseOrder');
    }

    public function view(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('View:PurchaseOrder');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchaseOrder');
    }

    public function update(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('Update:PurchaseOrder');
    }

    public function updateAny(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('UpdateAny:PurchaseOrder');
    }

    public function delete(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('Delete:PurchaseOrder');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchaseOrder');
    }

}