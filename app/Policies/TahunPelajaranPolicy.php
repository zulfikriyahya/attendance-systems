<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;

class TahunPelajaranPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_tahun::pelajaran');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TahunPelajaran $tahunPelajaran): bool
    {
        return $user->can('view_tahun::pelajaran');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_tahun::pelajaran');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TahunPelajaran $tahunPelajaran): bool
    {
        return $user->can('update_tahun::pelajaran');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TahunPelajaran $tahunPelajaran): bool
    {
        return $user->can('delete_tahun::pelajaran');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_tahun::pelajaran');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, TahunPelajaran $tahunPelajaran): bool
    {
        return $user->can('force_delete_tahun::pelajaran');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_tahun::pelajaran');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, TahunPelajaran $tahunPelajaran): bool
    {
        return $user->can('restore_tahun::pelajaran');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_tahun::pelajaran');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, TahunPelajaran $tahunPelajaran): bool
    {
        return $user->can('{{ Replicate }}');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
