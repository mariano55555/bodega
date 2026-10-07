<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;

/**
 * Single place where stock quantities and the weighted average cost are updated.
 *
 * Every module that moves stock (purchases, donations, production, adjustments,
 * transfers, dispatches and their reversals) must go through applyMovement() so the
 * inventory row keeps one consistent average cost per product and warehouse.
 */
class InventoryValuationService
{
    /**
     * Current weighted average cost of a product in a warehouse (0 when there is no inventory row).
     */
    public function currentAverageCost(int $productId, int $warehouseId): float
    {
        $inventory = Inventory::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return (float) ($inventory?->unit_cost ?? 0);
    }

    /**
     * Apply a kardex movement to the inventory row of its product and warehouse.
     *
     * Inbound quantities recalculate the weighted average with the movement cost.
     * Outbound quantities only reduce stock; the average does not change.
     * The resulting average is written back to the movement for reporting.
     */
    public function applyMovement(InventoryMovement $movement): Inventory
    {
        $inventory = Inventory::query()
            ->where('product_id', $movement->product_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            $inventory = new Inventory([
                'product_id' => $movement->product_id,
                'warehouse_id' => $movement->warehouse_id,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'unit_cost' => 0,
                'created_by' => $movement->created_by,
            ]);
        }

        $currentQuantity = (float) ($inventory->quantity ?? 0);
        $currentCost = (float) ($inventory->unit_cost ?? 0);
        $quantityIn = (float) ($movement->quantity_in ?? 0);
        $quantityOut = (float) ($movement->quantity_out ?? 0);

        $newQuantity = $currentQuantity + $quantityIn - $quantityOut;
        $newCost = $currentCost;

        if ($quantityIn > 0 && $movement->unit_cost !== null) {
            $newCost = $this->weightedAverage($currentQuantity, $currentCost, $quantityIn, (float) $movement->unit_cost);

            if ($movement->lot_number) {
                $inventory->lot_number = $movement->lot_number;
            }
            if ($movement->expiration_date) {
                $inventory->expiration_date = $movement->expiration_date;
            }
        }

        $inventory->quantity = $newQuantity;
        $inventory->unit_cost = round($newCost, 5);
        $inventory->is_active = true;
        $inventory->active_at = $inventory->active_at ?? now();
        $inventory->updated_by = $movement->created_by ?? $inventory->updated_by;
        $inventory->save();

        $movement->updateQuietly([
            'balance_unit_cost' => $inventory->unit_cost,
            'balance_total_cost' => round($newQuantity * (float) $inventory->unit_cost, 5),
        ]);

        return $inventory;
    }

    /**
     * Weighted average: (existing value + incoming value) / total quantity.
     * When nothing is in stock the incoming cost becomes the new average.
     */
    public function weightedAverage(float $currentQuantity, float $currentCost, float $incomingQuantity, float $incomingCost): float
    {
        if ($currentQuantity <= 0 || $incomingQuantity <= 0) {
            return $incomingQuantity > 0 ? $incomingCost : $currentCost;
        }

        $totalQuantity = $currentQuantity + $incomingQuantity;

        return (($currentQuantity * $currentCost) + ($incomingQuantity * $incomingCost)) / $totalQuantity;
    }
}
