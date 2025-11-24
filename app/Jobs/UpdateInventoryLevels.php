<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductLot;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInventoryLevels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public InventoryMovement $movement
    ) {
        $this->onQueue('inventory-updates');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Actualizando niveles de inventario', [
                'movement_id' => $this->movement->id,
                'product_id' => $this->movement->product_id,
                'warehouse_id' => $this->movement->warehouse_id,
                'quantity' => $this->movement->quantity,
            ]);

            DB::transaction(function () {
                $this->updateInventoryRecord();
                $this->updateProductLotQuantity();
                $this->checkInventoryAlerts();
            });

            Log::info('Niveles de inventario actualizados exitosamente', [
                'movement_id' => $this->movement->id,
                'product_id' => $this->movement->product_id,
            ]);

        } catch (Exception $e) {
            Log::error('Error actualizando niveles de inventario', [
                'movement_id' => $this->movement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update the main inventory record.
     */
    private function updateInventoryRecord(): void
    {
        $inventory = Inventory::firstOrCreate(
            [
                'product_id' => $this->movement->product_id,
                'warehouse_id' => $this->movement->warehouse_id,
                'storage_location_id' => $this->movement->to_storage_location_id,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'unit_cost' => $this->movement->unit_cost ?? 0,
                'lot_number' => $this->movement->lot_number,
                'expiration_date' => $this->movement->expiration_date,
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $this->movement->created_by,
            ]
        );

        // Calculate quantity change based on movement type
        $quantityChange = $this->calculateQuantityChange();

        // Update inventory quantities
        $inventory->quantity = max(0, $inventory->quantity + $quantityChange);

        // Update cost if it's an inbound movement with cost
        if ($this->isInboundMovement() && $this->movement->unit_cost) {
            $inventory->unit_cost = $this->calculateWeightedAverageCost($inventory);
        }

        $inventory->updated_by = $this->movement->completed_by;
        $inventory->save();

        // Update movement with actual inventory changes
        $this->movement->update([
            'previous_quantity' => $inventory->quantity - $quantityChange,
            'new_quantity' => $inventory->quantity,
        ]);
    }

    /**
     * Update product lot quantity if lot tracking is enabled.
     */
    private function updateProductLotQuantity(): void
    {
        if (! $this->movement->product_lot_id) {
            return;
        }

        $lot = ProductLot::find($this->movement->product_lot_id);
        if (! $lot) {
            return;
        }

        $quantityChange = $this->calculateQuantityChange();

        if ($quantityChange > 0) {
            $lot->increaseQuantity($quantityChange);
        } else {
            $lot->reduceQuantity(abs($quantityChange));
        }

        // Update lot status based on remaining quantity
        if ($lot->quantity_remaining <= 0) {
            $lot->update(['status' => 'consumed']);
        }
    }

    /**
     * Check for inventory alerts after the update.
     */
    private function checkInventoryAlerts(): void
    {
        // Check for low stock alerts
        $inventory = Inventory::where('product_id', $this->movement->product_id)
            ->where('warehouse_id', $this->movement->warehouse_id)
            ->first();

        if ($inventory && $inventory->product) {
            $minStockLevel = $inventory->product->min_stock_level ?? 0;

            if ($inventory->quantity <= $minStockLevel) {
                // Dispatch job to create inventory alert
                Log::info('Stock bajo detectado', [
                    'product_id' => $this->movement->product_id,
                    'current_quantity' => $inventory->quantity,
                    'min_stock_level' => $minStockLevel,
                ]);

                // Here you could dispatch another job or fire an event for low stock alert
            }
        }
    }

    /**
     * Calculate the quantity change based on movement type.
     */
    private function calculateQuantityChange(): float
    {
        $inboundTypes = ['in', 'transfer'];
        $adjustmentTypes = ['adjustment'];

        if (in_array($this->movement->movement_type, $inboundTypes)) {
            return $this->movement->quantity;
        }

        if ($this->movement->movement_type === 'out') {
            return -$this->movement->quantity;
        }

        if (in_array($this->movement->movement_type, $adjustmentTypes)) {
            // For adjustments, quantity can be positive or negative
            return $this->movement->quantity;
        }

        return 0;
    }

    /**
     * Check if this is an inbound movement.
     */
    private function isInboundMovement(): bool
    {
        return in_array($this->movement->movement_type, ['in', 'transfer']);
    }

    /**
     * Calculate weighted average cost for inventory valuation.
     */
    private function calculateWeightedAverageCost(Inventory $inventory): float
    {
        $currentValue = $inventory->quantity * $inventory->unit_cost;
        $incomingValue = $this->movement->quantity * $this->movement->unit_cost;
        $totalQuantity = $inventory->quantity + $this->movement->quantity;

        if ($totalQuantity == 0) {
            return $this->movement->unit_cost;
        }

        return ($currentValue + $incomingValue) / $totalQuantity;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Falló la actualización de niveles de inventario', [
            'movement_id' => $this->movement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
