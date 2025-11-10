<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Category');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('View:Category');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Category');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('Update:Category');
    }

    public function updateAny(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('UpdateAny:Category');
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('Delete:Category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Category');
    }

}