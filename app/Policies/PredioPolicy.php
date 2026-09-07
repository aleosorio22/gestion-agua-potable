<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Predio;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PredioPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Predio');
    }

    public function view(AuthUser $authUser, Predio $predio): bool
    {
        return $authUser->can('View:Predio');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Predio');
    }

    public function update(AuthUser $authUser, Predio $predio): bool
    {
        return $authUser->can('Update:Predio');
    }

    public function delete(AuthUser $authUser, Predio $predio): bool
    {
        return $authUser->can('Delete:Predio');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Predio');
    }

    public function restore(AuthUser $authUser, Predio $predio): bool
    {
        return $authUser->can('Restore:Predio');
    }

    /**
     * Nadie borra un predio de la base, ni con permiso de Shield: la propiedad es lo permanente
     * del padrón: el medidor se cambia, el predio queda.
     * Lo más lejos que llega la baja es `deleted_at`.
     */
    public function forceDelete(AuthUser $authUser, Predio $predio): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Predio');
    }

    public function replicate(AuthUser $authUser, Predio $predio): bool
    {
        return $authUser->can('Replicate:Predio');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Predio');
    }
}
