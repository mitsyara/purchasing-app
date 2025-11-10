<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CustomsData;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomsDataPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomsData');
    }

    public function view(AuthUser $authUser, CustomsData $customsData): bool
    {
        return $authUser->can('View:CustomsData');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomsData');
    }

    public function update(AuthUser $authUser, CustomsData $customsData): bool
    {
        return $authUser->can('Update:CustomsData');
    }

    public function updateAny(AuthUser $authUser, CustomsData $customsData): bool
    {
        return $authUser->can('UpdateAny:CustomsData');
    }

    public function delete(AuthUser $authUser, CustomsData $customsData): bool
    {
        return $authUser->can('Delete:CustomsData');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomsData');
    }

}