<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Periodo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PeriodoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Periodo');
    }

    public function view(AuthUser $authUser, Periodo $periodo): bool
    {
        return $authUser->can('View:Periodo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Periodo');
    }

    public function update(AuthUser $authUser, Periodo $periodo): bool
    {
        return $authUser->can('Update:Periodo');
    }

    public function delete(AuthUser $authUser, Periodo $periodo): bool
    {
        return $authUser->can('Delete:Periodo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Periodo');
    }

    public function restore(AuthUser $authUser, Periodo $periodo): bool
    {
        return $authUser->can('Restore:Periodo');
    }

    public function forceDelete(AuthUser $authUser, Periodo $periodo): bool
    {
        return $authUser->can('ForceDelete:Periodo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Periodo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Periodo');
    }

    public function replicate(AuthUser $authUser, Periodo $periodo): bool
    {
        return $authUser->can('Replicate:Periodo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Periodo');
    }
}
