<?php

namespace App\Policies;

use App\Models\Dispatch;
use App\Models\User;

class DispatchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // User can view dispatches if they have access to any warehouse in their company
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dispatch $dispatch): bool
    {
        // User can view if dispatch belongs to their company
        return $user->company_id === $dispatch->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // User can create dispatches if they belong to a company
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dispatch $dispatch): bool
    {
        // User can only update dispatches from their company that are still draft or pending
        return $user->company_id === $dispatch->company_id
            && in_array($dispatch->status, ['borrador', 'pendiente']);
    }

    /**
     * Determine whether the user can approve the dispatch.
     */
    public function approve(User $user, Dispatch $dispatch): bool
    {
        // User can approve if same company and dispatch is pending
        return $user->company_id === $dispatch->company_id
            && $dispatch->status === 'pendiente';
    }

    /**
     * Determine whether the user can dispatch (ship) the dispatch.
     */
    public function dispatch(User $user, Dispatch $dispatch): bool
    {
        // User can dispatch if same company and dispatch is approved
        return $user->company_id === $dispatch->company_id
            && $dispatch->status === 'aprobado';
    }

    /**
     * Determine whether the user can deliver the dispatch.
     */
    public function deliver(User $user, Dispatch $dispatch): bool
    {
        // User can deliver if same company and dispatch is dispatched
        return $user->company_id === $dispatch->company_id
            && $dispatch->status === 'despachado';
    }

    /**
     * Determine whether the user can cancel the dispatch.
     */
    public function cancel(User $user, Dispatch $dispatch): bool
    {
        // User can cancel if same company and dispatch is not yet dispatched or delivered
        return $user->company_id === $dispatch->company_id
            && ! in_array($dispatch->status, ['despachado', 'entregado', 'cancelado']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dispatch $dispatch): bool
    {
        // Only allow deletion of cancelled dispatches from same company
        return $user->company_id === $dispatch->company_id
            && $dispatch->status === 'cancelado';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dispatch $dispatch): bool
    {
        return $user->company_id === $dispatch->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dispatch $dispatch): bool
    {
        // Prevent permanent deletion in normal circumstances
        return false;
    }
}
