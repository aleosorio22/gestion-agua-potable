<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contador;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ContadorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Contador');
    }

    public function view(AuthUser $authUser, Contador $contador): bool
    {
        return $authUser->can('View:Contador');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Contador');
    }

    public function update(AuthUser $authUser, Contador $contador): bool
    {
        return $authUser->can('Update:Contador');
    }

    public function delete(AuthUser $authUser, Contador $contador): bool
    {
        return $authUser->can('Delete:Contador');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Contador');
    }

    public function restore(AuthUser $authUser, Contador $contador): bool
    {
        return $authUser->can('Restore:Contador');
    }

    /**
     * Nadie borra un contador de la base, ni con permiso de Shield: sus lecturas son el respaldo
     * de lo que se le cobró a cada vecino.
     * Lo más lejos que llega la baja es `deleted_at`.
     */
    public function forceDelete(AuthUser $authUser, Contador $contador): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Contador');
    }

    public function replicate(AuthUser $authUser, Contador $contador): bool
    {
        return $authUser->can('Replicate:Contador');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Contador');
    }
}
