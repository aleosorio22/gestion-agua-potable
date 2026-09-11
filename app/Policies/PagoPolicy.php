<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pago;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Un pago no se borra ni con permiso de Shield: es el respaldo de que alguien
 * entregó dinero en ventanilla. Para dejarlo sin efecto se revierte, con autor
 * y motivo, que es lo que `PagoObserver` impone a nivel de modelo.
 */
class PagoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pago');
    }

    public function view(AuthUser $authUser, Pago $pago): bool
    {
        return $authUser->can('View:Pago');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pago');
    }

    public function update(AuthUser $authUser, Pago $pago): bool
    {
        return $authUser->can('Update:Pago');
    }

    public function delete(AuthUser $authUser, Pago $pago): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Pago $pago): bool
    {
        return $authUser->can('Restore:Pago');
    }

    public function forceDelete(AuthUser $authUser, Pago $pago): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pago');
    }

    public function replicate(AuthUser $authUser, Pago $pago): bool
    {
        return $authUser->can('Replicate:Pago');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pago');
    }
}
