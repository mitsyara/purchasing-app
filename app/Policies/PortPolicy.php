<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Port;
use Illuminate\Auth\Access\HandlesAuthorization;

class PortPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Port');
    }

    public function view(AuthUser $authUser, Port $port): bool
    {
        return $authUser->can('View:Port');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Port');
    }

    public function update(AuthUser $authUser, Port $port): bool
    {
        return $authUser->can('Update:Port');
    }

    public function updateAny(AuthUser $authUser, Port $port): bool
    {
        return $authUser->can('UpdateAny:Port');
    }

    public function delete(AuthUser $authUser, Port $port): bool
    {
        return $authUser->can('Delete:Port');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Port');
    }

}