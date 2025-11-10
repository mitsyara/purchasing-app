<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Product');
    }

    public function view(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('View:Product');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Product');
    }

    public function update(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Update:Product');
    }

    public function updateAny(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('UpdateAny:Product');
    }

    public function delete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Delete:Product');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Product');
    }

}