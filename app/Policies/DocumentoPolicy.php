<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Documento;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Documento');
    }

    public function view(AuthUser $authUser, Documento $documento): bool
    {
        return $authUser->can('View:Documento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Documento');
    }

    public function update(AuthUser $authUser, Documento $documento): bool
    {
        return $authUser->can('Update:Documento');
    }

    public function delete(AuthUser $authUser, Documento $documento): bool
    {
        return $authUser->can('Delete:Documento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Documento');
    }

    public function restore(AuthUser $authUser, Documento $documento): bool
    {
        return $authUser->can('Restore:Documento');
    }

    public function forceDelete(AuthUser $authUser, Documento $documento): bool
    {
        return $authUser->can('ForceDelete:Documento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Documento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Documento');
    }

    public function replicate(AuthUser $authUser, Documento $documento): bool
    {
        return $authUser->can('Replicate:Documento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Documento');
    }
}
