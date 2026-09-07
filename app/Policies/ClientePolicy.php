<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cliente;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ClientePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Cliente');
    }

    public function view(AuthUser $authUser, Cliente $cliente): bool
    {
        return $authUser->can('View:Cliente');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Cliente');
    }

    public function update(AuthUser $authUser, Cliente $cliente): bool
    {
        return $authUser->can('Update:Cliente');
    }

    public function delete(AuthUser $authUser, Cliente $cliente): bool
    {
        return $authUser->can('Delete:Cliente');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Cliente');
    }

    public function restore(AuthUser $authUser, Cliente $cliente): bool
    {
        return $authUser->can('Restore:Cliente');
    }

    /**
     * Nadie borra a un cliente de la base, ni con permiso de Shield: un titular
     * del servicio respalda boletas y pagos que la entidad tiene que poder
     * mostrar años después. Lo más lejos que llega la baja es `deleted_at`.
     */
    public function forceDelete(AuthUser $authUser, Cliente $cliente): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Cliente');
    }

    public function replicate(AuthUser $authUser, Cliente $cliente): bool
    {
        return $authUser->can('Replicate:Cliente');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Cliente');
    }
}
