<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SerieDocumento;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SerieDocumentoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SerieDocumento');
    }

    public function view(AuthUser $authUser, SerieDocumento $serieDocumento): bool
    {
        return $authUser->can('View:SerieDocumento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SerieDocumento');
    }

    public function update(AuthUser $authUser, SerieDocumento $serieDocumento): bool
    {
        return $authUser->can('Update:SerieDocumento');
    }

    public function delete(AuthUser $authUser, SerieDocumento $serieDocumento): bool
    {
        return $authUser->can('Delete:SerieDocumento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SerieDocumento');
    }

    public function restore(AuthUser $authUser, SerieDocumento $serieDocumento): bool
    {
        return $authUser->can('Restore:SerieDocumento');
    }

    public function forceDelete(AuthUser $authUser, SerieDocumento $serieDocumento): bool
    {
        return $authUser->can('ForceDelete:SerieDocumento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SerieDocumento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SerieDocumento');
    }

    public function replicate(AuthUser $authUser, SerieDocumento $serieDocumento): bool
    {
        return $authUser->can('Replicate:SerieDocumento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SerieDocumento');
    }
}
