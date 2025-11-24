<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserActivityLog;

class UserActivityLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasPermissionTo('activity-logs.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserActivityLog $userActivityLog): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $userActivityLog->company_id === $user->company_id;
        }

        // Users can view their own activity logs
        if ($user->id === $userActivityLog->user_id) {
            return true;
        }

        return $user->hasPermissionTo('activity-logs.view')
            && $userActivityLog->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Activity logs are created automatically by the system
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserActivityLog $userActivityLog): bool
    {
        // Activity logs should not be modified once created
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserActivityLog $userActivityLog): bool
    {
        // Only super admins can delete activity logs for compliance reasons
        return $user->isSuperAdmin()
            && ! $userActivityLog->is_sensitive; // Sensitive logs cannot be deleted
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserActivityLog $userActivityLog): bool
    {
        return false; // Activity logs should not be restored
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserActivityLog $userActivityLog): bool
    {
        return false; // Activity logs should never be permanently deleted
    }

    /**
     * Determine whether the user can view sensitive activity logs.
     */
    public function viewSensitive(User $user, UserActivityLog $userActivityLog): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin() && $userActivityLog->company_id === $user->company_id) {
            return $user->hasPermissionTo('activity-logs.view-sensitive');
        }

        return false;
    }

    /**
     * Determine whether the user can export activity logs.
     */
    public function export(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isCompanyAdmin() && $user->hasPermissionTo('activity-logs.export'))
            || $user->hasPermissionTo('activity-logs.export');
    }

    /**
     * Determine whether the user can view activity logs for a specific user.
     */
    public function viewForUser(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $targetUser->company_id === $user->company_id;
        }

        // Users can view their own logs
        if ($user->id === $targetUser->id) {
            return true;
        }

        return $user->hasPermissionTo('activity-logs.view-others')
            && $targetUser->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can view activity logs by action type.
     */
    public function viewByAction(User $user, string $action): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $sensitiveActions = [
            'role_assigned',
            'role_removed',
            'permission_granted',
            'permission_revoked',
            'warehouse_access_granted',
            'warehouse_access_revoked',
            'password_changed',
            'failed_login',
        ];

        if (in_array($action, $sensitiveActions)) {
            return $user->isCompanyAdmin()
                || $user->hasPermissionTo('activity-logs.view-sensitive');
        }

        return $user->isCompanyAdmin()
            || $user->hasPermissionTo('activity-logs.view');
    }

    /**
     * Determine whether the user can view activity logs for a specific date range.
     */
    public function viewByDateRange(User $user, string $startDate, string $endDate): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $user->hasPermissionTo('activity-logs.view-historical');
        }

        return $user->hasPermissionTo('activity-logs.view-historical');
    }
}
