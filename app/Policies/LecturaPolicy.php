<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lectura;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LecturaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Lectura');
    }

    public function view(AuthUser $authUser, Lectura $lectura): bool
    {
        return $authUser->can('View:Lectura');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Lectura');
    }

    public function update(AuthUser $authUser, Lectura $lectura): bool
    {
        return $authUser->can('Update:Lectura');
    }

    public function delete(AuthUser $authUser, Lectura $lectura): bool
    {
        return $authUser->can('Delete:Lectura');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Lectura');
    }

    public function restore(AuthUser $authUser, Lectura $lectura): bool
    {
        return $authUser->can('Restore:Lectura');
    }

    public function forceDelete(AuthUser $authUser, Lectura $lectura): bool
    {
        return $authUser->can('ForceDelete:Lectura');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Lectura');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Lectura');
    }

    public function replicate(AuthUser $authUser, Lectura $lectura): bool
    {
        return $authUser->can('Replicate:Lectura');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Lectura');
    }
}
