<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MetodoPago;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MetodoPagoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MetodoPago');
    }

    public function view(AuthUser $authUser, MetodoPago $metodoPago): bool
    {
        return $authUser->can('View:MetodoPago');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MetodoPago');
    }

    public function update(AuthUser $authUser, MetodoPago $metodoPago): bool
    {
        return $authUser->can('Update:MetodoPago');
    }

    public function delete(AuthUser $authUser, MetodoPago $metodoPago): bool
    {
        return $authUser->can('Delete:MetodoPago');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MetodoPago');
    }

    public function restore(AuthUser $authUser, MetodoPago $metodoPago): bool
    {
        return $authUser->can('Restore:MetodoPago');
    }

    public function forceDelete(AuthUser $authUser, MetodoPago $metodoPago): bool
    {
        return $authUser->can('ForceDelete:MetodoPago');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MetodoPago');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MetodoPago');
    }

    public function replicate(AuthUser $authUser, MetodoPago $metodoPago): bool
    {
        return $authUser->can('Replicate:MetodoPago');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MetodoPago');
    }
}
