<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Boleta;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Una boleta emitida es un documento contable: no se borra ni con permiso de
 * Shield. Para dejarla sin efecto se anula, con autor y motivo, que es lo que
 * `BoletaObserver` impone a nivel de modelo.
 */
class BoletaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Boleta');
    }

    public function view(AuthUser $authUser, Boleta $boleta): bool
    {
        return $authUser->can('View:Boleta');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Boleta');
    }

    public function update(AuthUser $authUser, Boleta $boleta): bool
    {
        return $authUser->can('Update:Boleta');
    }

    public function delete(AuthUser $authUser, Boleta $boleta): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Boleta $boleta): bool
    {
        return $authUser->can('Restore:Boleta');
    }

    public function forceDelete(AuthUser $authUser, Boleta $boleta): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Boleta');
    }

    public function replicate(AuthUser $authUser, Boleta $boleta): bool
    {
        return $authUser->can('Replicate:Boleta');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Boleta');
    }
}
