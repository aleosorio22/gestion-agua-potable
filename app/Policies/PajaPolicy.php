<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Paja;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PajaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Paja');
    }

    public function view(AuthUser $authUser, Paja $paja): bool
    {
        return $authUser->can('View:Paja');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Paja');
    }

    public function update(AuthUser $authUser, Paja $paja): bool
    {
        return $authUser->can('Update:Paja');
    }

    public function delete(AuthUser $authUser, Paja $paja): bool
    {
        return $authUser->can('Delete:Paja');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Paja');
    }

    public function restore(AuthUser $authUser, Paja $paja): bool
    {
        return $authUser->can('Restore:Paja');
    }

    public function forceDelete(AuthUser $authUser, Paja $paja): bool
    {
        return $authUser->can('ForceDelete:Paja');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Paja');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Paja');
    }

    public function replicate(AuthUser $authUser, Paja $paja): bool
    {
        return $authUser->can('Replicate:Paja');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Paja');
    }
}
