<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tarifa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TarifaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tarifa');
    }

    public function view(AuthUser $authUser, Tarifa $tarifa): bool
    {
        return $authUser->can('View:Tarifa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tarifa');
    }

    public function update(AuthUser $authUser, Tarifa $tarifa): bool
    {
        return $authUser->can('Update:Tarifa');
    }

    public function delete(AuthUser $authUser, Tarifa $tarifa): bool
    {
        return $authUser->can('Delete:Tarifa');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Tarifa');
    }

    public function restore(AuthUser $authUser, Tarifa $tarifa): bool
    {
        return $authUser->can('Restore:Tarifa');
    }

    public function forceDelete(AuthUser $authUser, Tarifa $tarifa): bool
    {
        return $authUser->can('ForceDelete:Tarifa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tarifa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tarifa');
    }

    public function replicate(AuthUser $authUser, Tarifa $tarifa): bool
    {
        return $authUser->can('Replicate:Tarifa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tarifa');
    }
}
