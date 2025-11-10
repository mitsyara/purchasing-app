<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Assortment;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssortmentPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Assortment');
    }

    public function view(AuthUser $authUser, Assortment $assortment): bool
    {
        return $authUser->can('View:Assortment');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Assortment');
    }

    public function update(AuthUser $authUser, Assortment $assortment): bool
    {
        return $authUser->can('Update:Assortment');
    }

    public function updateAny(AuthUser $authUser, Assortment $assortment): bool
    {
        return $authUser->can('UpdateAny:Assortment');
    }

    public function delete(AuthUser $authUser, Assortment $assortment): bool
    {
        return $authUser->can('Delete:Assortment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Assortment');
    }

}