<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only users with warehouse management roles can view companies
        return $user->hasRole(['super-admin', 'company-admin', 'branch-manager', 'warehouse-manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        // Super admins can view all companies
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users can only view their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super admins can create companies
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        // Super admins can update any company
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Company admins can update their own company
        return $user->hasRole('company-admin') && $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only super admins can delete companies
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        // Only super admins can restore companies
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        // Only super admins can force delete companies
        return $user->hasRole('super-admin');
    }
}
