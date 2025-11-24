<?php

namespace App\Policies;

use App\Models\AlertSetting;
use App\Models\User;

class AlertSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view alert settings');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AlertSetting $alertSetting): bool
    {
        return $user->can('view alert settings') &&
               $user->company_id === $alertSetting->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create alert settings');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AlertSetting $alertSetting): bool
    {
        return $user->can('edit alert settings') &&
               $user->company_id === $alertSetting->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AlertSetting $alertSetting): bool
    {
        return $user->can('delete alert settings') &&
               $user->company_id === $alertSetting->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AlertSetting $alertSetting): bool
    {
        return $user->can('delete alert settings') &&
               $user->company_id === $alertSetting->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AlertSetting $alertSetting): bool
    {
        return $user->can('delete alert settings') &&
               $user->company_id === $alertSetting->company_id;
    }
}
