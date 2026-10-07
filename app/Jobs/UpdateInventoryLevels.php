<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductLot;
use App\Services\InventoryValuationService;
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
     * Update the main inventory record (stock and weighted average cost).
     */
    private function updateInventoryRecord(): void
    {
        app(InventoryValuationService::class)->applyMovement($this->movement);
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
        $inboundTypes = ['in', 'transfer', 'transfer_in'];
        $outboundTypes = ['out', 'transfer_out'];

        if (in_array($this->movement->movement_type, $inboundTypes)) {
            return abs($this->movement->quantity);
        }

        if (in_array($this->movement->movement_type, $outboundTypes)) {
            return -abs($this->movement->quantity);
        }

        if ($this->movement->movement_type === 'adjustment') {
            // For adjustments, quantity can be positive or negative
            return $this->movement->quantity;
        }

        return 0;
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
