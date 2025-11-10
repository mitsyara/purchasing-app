<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Contact;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;
    
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Contact');
    }

    public function view(AuthUser $authUser, Contact $contact): bool
    {
        return $authUser->can('View:Contact');
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Contact');
    }

    public function update(AuthUser $authUser, Contact $contact): bool
    {
        return $authUser->can('Update:Contact');
    }

    public function updateAny(AuthUser $authUser, Contact $contact): bool
    {
        return $authUser->can('UpdateAny:Contact');
    }

    public function delete(AuthUser $authUser, Contact $contact): bool
    {
        return $authUser->can('Delete:Contact');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Contact');
    }

}