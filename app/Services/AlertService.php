<?php

namespace App\Services;

use App\Mail\AlertNotification;
use App\Models\AlertSetting;
use App\Models\Inventory;
use App\Models\InventoryAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    protected ?AlertSetting $settings = null;

    /**
     * Get or load alert settings for a company
     */
    protected function getSettings(int $companyId): ?AlertSetting
    {
        if ($this->settings === null || $this->settings->company_id !== $companyId) {
            $this->settings = AlertSetting::getForCompany($companyId);
        }

        return $this->settings;
    }

    /**
     * Check for low stock and create alerts
     */
    public function checkLowStockAlerts(int $companyId): int
    {
        $alertsCreated = 0;
        $settings = $this->getSettings($companyId);

        $lowStockInventories = Inventory::whereHas('product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse'])
            ->get()
            ->filter(function ($inventory) {
                return $inventory->product->minimum_stock &&
                       $inventory->quantity < $inventory->product->minimum_stock &&
                       $inventory->quantity > 0;
            });

        foreach ($lowStockInventories as $inventory) {
            $existingAlert = InventoryAlert::where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('alert_type', 'low_stock')
                ->where('is_resolved', false)
                ->first();

            if (! $existingAlert) {
                $priority = $this->calculatePriority($inventory->quantity, $inventory->product->minimum_stock, $settings);

                $alert = InventoryAlert::create([
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'alert_type' => 'low_stock',
                    'threshold_value' => $inventory->product->minimum_stock,
                    'current_value' => $inventory->quantity,
                    'priority' => $priority,
                    'message' => "Stock bajo: {$inventory->product->name} tiene {$inventory->quantity} unidades (mínimo: {$inventory->product->minimum_stock})",
                    'metadata' => [
                        'product_name' => $inventory->product->name,
                        'warehouse_name' => $inventory->warehouse->name,
                        'sku' => $inventory->product->sku,
                    ],
                    'is_active' => true,
                    'auto_resolve' => true,
                ]);

                // Send email notification if enabled
                $this->sendEmailNotification($alert, $settings);

                $alertsCreated++;
            } else {
                // Update existing alert with current values
                $existingAlert->update([
                    'current_value' => $inventory->quantity,
                    'priority' => $this->calculatePriority($inventory->quantity, $inventory->product->minimum_stock, $settings),
                ]);
            }
        }

        return $alertsCreated;
    }

    /**
     * Check for out of stock and create alerts
     */
    public function checkOutOfStockAlerts(int $companyId): int
    {
        $alertsCreated = 0;
        $settings = $this->getSettings($companyId);

        $outOfStockInventories = Inventory::whereHas('product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('quantity', '<=', 0)
            ->with(['product', 'warehouse'])
            ->get();

        foreach ($outOfStockInventories as $inventory) {
            $existingAlert = InventoryAlert::where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('alert_type', 'out_of_stock')
                ->where('is_resolved', false)
                ->first();

            if (! $existingAlert) {
                $alert = InventoryAlert::create([
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'alert_type' => 'out_of_stock',
                    'threshold_value' => 0,
                    'current_value' => $inventory->quantity,
                    'priority' => 'high',
                    'message' => "Sin stock: {$inventory->product->name} en {$inventory->warehouse->name}",
                    'metadata' => [
                        'product_name' => $inventory->product->name,
                        'warehouse_name' => $inventory->warehouse->name,
                        'sku' => $inventory->product->sku,
                    ],
                    'is_active' => true,
                    'auto_resolve' => true,
                ]);

                // Send email notification if enabled
                $this->sendEmailNotification($alert, $settings);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Check for expiring products and create alerts
     */
    public function checkExpiringProductsAlerts(int $companyId): int
    {
        $alertsCreated = 0;
        $settings = $this->getSettings($companyId);

        // Use settings threshold or default to 30 days
        $daysThreshold = $settings ? $settings->expiring_soon_days : 30;

        // Query inventory with expiring lots
        $expiringInventories = Inventory::whereHas('product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>', now())
            ->where('expiration_date', '<=', now()->addDays($daysThreshold))
            ->with(['product', 'warehouse'])
            ->get();

        foreach ($expiringInventories as $inventory) {
            $existingAlert = InventoryAlert::where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('alert_type', 'expiring_soon')
                ->where('is_resolved', false)
                ->whereJsonContains('metadata->lot_number', $inventory->lot_number)
                ->first();

            if (! $existingAlert) {
                $daysUntilExpiry = now()->diffInDays($inventory->expiration_date, false);

                // Calculate priority using settings if available
                $priority = $settings
                    ? $settings->getPriorityForExpiringDays($daysUntilExpiry)
                    : ($daysUntilExpiry <= 7 ? 'critical' : ($daysUntilExpiry <= 15 ? 'high' : 'medium'));

                $alert = InventoryAlert::create([
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'alert_type' => 'expiring_soon',
                    'threshold_value' => $daysThreshold,
                    'current_value' => $daysUntilExpiry,
                    'priority' => $priority,
                    'message' => "Próximo a vencer: {$inventory->product->name} lote {$inventory->lot_number} vence en {$daysUntilExpiry} días ({$inventory->expiration_date->format('d/m/Y')})",
                    'metadata' => [
                        'product_name' => $inventory->product->name,
                        'warehouse_name' => $inventory->warehouse->name,
                        'lot_number' => $inventory->lot_number,
                        'expiration_date' => $inventory->expiration_date->format('Y-m-d'),
                        'quantity_remaining' => $inventory->quantity,
                    ],
                    'is_active' => true,
                    'auto_resolve' => true,
                    'expires_at' => $inventory->expiration_date,
                ]);

                // Send email notification if enabled
                $this->sendEmailNotification($alert, $settings);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Check for expired products and create alerts
     */
    public function checkExpiredProductsAlerts(int $companyId): int
    {
        $alertsCreated = 0;
        $settings = $this->getSettings($companyId);

        // Query inventory with expired lots
        $expiredInventories = Inventory::whereHas('product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now())
            ->with(['product', 'warehouse'])
            ->get();

        foreach ($expiredInventories as $inventory) {
            $existingAlert = InventoryAlert::where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('alert_type', 'expired')
                ->where('is_resolved', false)
                ->whereJsonContains('metadata->lot_number', $inventory->lot_number)
                ->first();

            if (! $existingAlert) {
                $alert = InventoryAlert::create([
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'alert_type' => 'expired',
                    'threshold_value' => 0,
                    'current_value' => now()->diffInDays($inventory->expiration_date, false),
                    'priority' => 'critical',
                    'message' => "VENCIDO: {$inventory->product->name} lote {$inventory->lot_number} venció el {$inventory->expiration_date->format('d/m/Y')}",
                    'metadata' => [
                        'product_name' => $inventory->product->name,
                        'warehouse_name' => $inventory->warehouse->name,
                        'lot_number' => $inventory->lot_number,
                        'expiration_date' => $inventory->expiration_date->format('Y-m-d'),
                        'quantity_remaining' => $inventory->quantity,
                    ],
                    'is_active' => true,
                    'auto_resolve' => false, // Expired products should not auto-resolve
                ]);

                // Send email notification if enabled (expired products are always critical)
                $this->sendEmailNotification($alert, $settings);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Auto-resolve alerts that no longer apply
     */
    public function autoResolveAlerts(int $companyId): int
    {
        $alertsResolved = 0;

        // Resolve low stock alerts where stock is now adequate
        $lowStockAlerts = InventoryAlert::where('alert_type', 'low_stock')
            ->where('is_resolved', false)
            ->where('auto_resolve', true)
            ->whereHas('product', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['product', 'warehouse'])
            ->get();

        foreach ($lowStockAlerts as $alert) {
            $inventory = Inventory::where('product_id', $alert->product_id)
                ->where('warehouse_id', $alert->warehouse_id)
                ->first();

            if ($inventory && $inventory->quantity >= $alert->product->minimum_stock) {
                $alert->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                    'resolution_notes' => 'Auto-resuelto: stock restaurado a nivel adecuado',
                ]);
                $alertsResolved++;
            }
        }

        // Resolve out of stock alerts where stock is now available
        $outOfStockAlerts = InventoryAlert::where('alert_type', 'out_of_stock')
            ->where('is_resolved', false)
            ->where('auto_resolve', true)
            ->whereHas('product', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();

        foreach ($outOfStockAlerts as $alert) {
            $inventory = Inventory::where('product_id', $alert->product_id)
                ->where('warehouse_id', $alert->warehouse_id)
                ->first();

            if ($inventory && $inventory->quantity > 0) {
                $alert->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                    'resolution_notes' => 'Auto-resuelto: stock disponible',
                ]);
                $alertsResolved++;
            }
        }

        return $alertsResolved;
    }

    /**
     * Check for stock overflow attempts (exit attempts exceeding available stock)
     * This creates alerts when someone tries to dispatch/exit more than available
     */
    public function checkStockOverflowAttempt(int $productId, int $warehouseId, float $requestedQuantity, string $operationType = 'exit'): ?InventoryAlert
    {
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->with(['product', 'warehouse'])
            ->first();

        if (! $inventory) {
            return null;
        }

        $availableQuantity = $inventory->available_quantity ?? $inventory->quantity;

        if ($requestedQuantity > $availableQuantity) {
            $existingAlert = InventoryAlert::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('alert_type', 'stock_overflow')
                ->where('is_resolved', false)
                ->whereDate('created_at', now())
                ->first();

            if (! $existingAlert) {
                $alert = InventoryAlert::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'alert_type' => 'stock_overflow',
                    'threshold_value' => $availableQuantity,
                    'current_value' => $requestedQuantity,
                    'priority' => 'high',
                    'message' => "Intento de {$operationType} superior a existencias: {$inventory->product->name} - Disponible: {$availableQuantity}, Solicitado: {$requestedQuantity}",
                    'metadata' => [
                        'product_name' => $inventory->product->name,
                        'warehouse_name' => $inventory->warehouse->name,
                        'sku' => $inventory->product->sku,
                        'available_quantity' => $availableQuantity,
                        'requested_quantity' => $requestedQuantity,
                        'operation_type' => $operationType,
                        'shortage' => $requestedQuantity - $availableQuantity,
                    ],
                    'is_active' => true,
                    'auto_resolve' => false,
                ]);

                return $alert;
            }

            return $existingAlert;
        }

        return null;
    }

    /**
     * Check if a transaction date falls within a closed period
     */
    public function checkClosedPeriodTransaction(int $companyId, int $warehouseId, \Carbon\Carbon $transactionDate): ?InventoryAlert
    {
        $closure = \App\Models\InventoryClosure::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'closed')
            ->where('closure_date', '>=', $transactionDate->startOfMonth())
            ->where('closure_date', '<=', $transactionDate->endOfMonth())
            ->with(['warehouse'])
            ->first();

        if ($closure) {
            $warehouse = \App\Models\Warehouse::find($warehouseId);

            $existingAlert = InventoryAlert::where('warehouse_id', $warehouseId)
                ->where('alert_type', 'closed_period')
                ->where('is_resolved', false)
                ->whereDate('created_at', now())
                ->whereJsonContains('metadata->closure_id', $closure->id)
                ->first();

            if (! $existingAlert) {
                $alert = InventoryAlert::create([
                    'product_id' => null,
                    'warehouse_id' => $warehouseId,
                    'alert_type' => 'closed_period',
                    'threshold_value' => 0,
                    'current_value' => 0,
                    'priority' => 'critical',
                    'message' => "Intento de transacción en período cerrado: {$warehouse->name} - Mes: {$transactionDate->format('m/Y')} (Cierre: {$closure->closure_date->format('d/m/Y')})",
                    'metadata' => [
                        'warehouse_name' => $warehouse->name,
                        'transaction_date' => $transactionDate->format('Y-m-d'),
                        'closure_id' => $closure->id,
                        'closure_date' => $closure->closure_date->format('Y-m-d'),
                        'period' => $transactionDate->format('Y-m'),
                    ],
                    'is_active' => true,
                    'auto_resolve' => false,
                ]);

                return $alert;
            }

            return $existingAlert;
        }

        return null;
    }

    /**
     * Run all alert checks for a company
     */
    public function runAllChecks(int $companyId): array
    {
        return [
            'low_stock_created' => $this->checkLowStockAlerts($companyId),
            'out_of_stock_created' => $this->checkOutOfStockAlerts($companyId),
            'expiring_created' => $this->checkExpiringProductsAlerts($companyId),
            'expired_created' => $this->checkExpiredProductsAlerts($companyId),
            'auto_resolved' => $this->autoResolveAlerts($companyId),
        ];
    }

    /**
     * Calculate priority based on stock level using AlertSetting thresholds
     */
    protected function calculatePriority(float $currentStock, float $minimumStock, ?AlertSetting $settings = null): string
    {
        $percentage = ($currentStock / $minimumStock) * 100;

        if ($settings) {
            return $settings->getPriorityForStockPercentage($percentage);
        }

        // Fallback to default percentages if no settings
        if ($percentage <= 25) {
            return 'critical';
        } elseif ($percentage <= 50) {
            return 'high';
        } elseif ($percentage <= 75) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Send email notification for an alert if enabled
     */
    protected function sendEmailNotification(InventoryAlert $alert, ?AlertSetting $settings = null): void
    {
        // If no settings or email alerts disabled, don't send
        if (! $settings || ! $settings->email_alerts_enabled) {
            return;
        }

        // Check if we should send email for this alert type and priority
        if (! $settings->shouldSendEmail($alert->alert_type, $alert->priority)) {
            return;
        }

        // Check if there are recipients configured
        if (empty($settings->email_recipients) || ! is_array($settings->email_recipients)) {
            return;
        }

        try {
            // Send email to all configured recipients
            Mail::to($settings->email_recipients)->send(new AlertNotification($alert));

            Log::info('Alert email sent successfully', [
                'alert_id' => $alert->id,
                'alert_type' => $alert->alert_type,
                'priority' => $alert->priority,
                'recipients' => $settings->email_recipients,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the alert creation
            Log::error('Failed to send alert email', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
                'recipients' => $settings->email_recipients,
            ]);
        }
    }
}
