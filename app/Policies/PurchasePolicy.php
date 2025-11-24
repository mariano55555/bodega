<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Purchase $purchase): bool
    {
        // Super admins can view all purchases
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can view purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can view purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        // Warehouse operators can view purchases for their warehouses
        if ($user->isWarehouseOperator()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super admins and company admins can create purchases
        if ($user->isSuperAdmin() || $user->isCompanyAdmin()) {
            return true;
        }

        // Warehouse managers can create purchases
        if ($user->isWarehouseManager()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        // Can only update purchases in draft or pending status
        if (! in_array($purchase->status, ['borrador', 'pendiente'])) {
            return false;
        }

        // Super admins can update any purchase
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can update purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can update purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        // Creator can update their own draft purchases
        if ($purchase->status === 'borrador' && $purchase->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        // Can only delete purchases in draft status
        if ($purchase->status !== 'borrador') {
            return false;
        }

        // Super admins can delete any draft purchase
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can delete draft purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can delete draft purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        // Creator can delete their own draft purchases
        if ($purchase->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can submit the model for approval.
     */
    public function submit(User $user, Purchase $purchase): bool
    {
        // Can only submit draft purchases
        if ($purchase->status !== 'borrador') {
            return false;
        }

        // Super admins can submit any purchase
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can submit purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can submit purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        // Creator can submit their own draft purchases
        if ($purchase->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Purchase $purchase): bool
    {
        // Can only approve pending purchases
        if ($purchase->status !== 'pendiente') {
            return false;
        }

        // Super admins can approve any purchase
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can approve purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can approve purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        return false;
    }

    /**
     * Determine whether the user can receive the model.
     */
    public function receive(User $user, Purchase $purchase): bool
    {
        // Can only receive approved purchases
        if ($purchase->status !== 'aprobado') {
            return false;
        }

        // Super admins can receive any purchase
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can receive purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can receive purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        // Warehouse operators can receive purchases for their warehouses
        if ($user->isWarehouseOperator()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, Purchase $purchase): bool
    {
        // Can only cancel draft or pending purchases
        if (! in_array($purchase->status, ['borrador', 'pendiente'])) {
            return false;
        }

        // Super admins can cancel any purchase
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Company admins can cancel purchases from their company
        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        // Warehouse managers can cancel purchases for their warehouses
        if ($user->isWarehouseManager()) {
            return $user->warehouses->contains('id', $purchase->warehouse_id);
        }

        // Creator can cancel their own purchases in draft status
        if ($purchase->status === 'borrador' && $purchase->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Purchase $purchase): bool
    {
        // Only super admins and company admins can restore
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin() && $user->company_id === $purchase->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Purchase $purchase): bool
    {
        // Only super admins can permanently delete
        return $user->isSuperAdmin();
    }
}
