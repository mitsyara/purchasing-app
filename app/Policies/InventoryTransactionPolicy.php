<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InventoryTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryTransactionPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryTransaction');
    }

    public function view(AuthUser $authUser, InventoryTransaction $inventoryTransaction): bool
    {
        return $authUser->can('View:InventoryTransaction');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryTransaction');
    }

    public function update(AuthUser $authUser, InventoryTransaction $inventoryTransaction): bool
    {
        return $authUser->can('Update:InventoryTransaction');
    }

    public function updateAny(AuthUser $authUser, InventoryTransaction $inventoryTransaction): bool
    {
        return $authUser->can('UpdateAny:InventoryTransaction');
    }

    public function delete(AuthUser $authUser, InventoryTransaction $inventoryTransaction): bool
    {
        return $authUser->can('Delete:InventoryTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InventoryTransaction');
    }

}