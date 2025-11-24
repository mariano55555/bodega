<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super admins and company admins can view users
        return $user->isSuperAdmin() || $user->isCompanyAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Super admins can view all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can view users in their company
        if ($user->isCompanyAdmin() && $user->company_id === $model->company_id) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super admins and company admins can create users
        return $user->isSuperAdmin() || $user->isCompanyAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Super admins can update all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can update users in their company
        if ($user->isCompanyAdmin() && $user->company_id === $model->company_id) {
            return true;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Prevent users from deleting themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can delete all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can delete users in their company
        if ($user->isCompanyAdmin() && $user->company_id === $model->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Super admins can restore all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can restore users in their company
        if ($user->isCompanyAdmin() && $user->company_id === $model->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admins can force delete
        return $user->isSuperAdmin();
    }
}
