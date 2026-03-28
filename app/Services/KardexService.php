<?php

namespace App\Services;

use App\Models\InventoryMovement;

class KardexService
{
    /**
     * Create an inventory movement and recalculate subsequent balances if needed.
     *
     * @param  array<string, mixed>  $movementData  Must include: warehouse_id, product_id, movement_date, quantity_in, quantity_out
     */
    public function createMovement(array $movementData): InventoryMovement
    {
        $warehouseId = $movementData['warehouse_id'];
        $productId = $movementData['product_id'];
        $movementDate = $movementData['movement_date'];
        $quantityIn = (float) ($movementData['quantity_in'] ?? 0);
        $quantityOut = (float) ($movementData['quantity_out'] ?? 0);

        // Find the last existing movement on or before the target date
        $previousMovement = InventoryMovement::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNotNull('balance_quantity')
            ->where('movement_date', '<=', $movementDate)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $previousBalance = $previousMovement ? (float) $previousMovement->balance_quantity : 0;
        $newBalance = $previousBalance + $quantityIn - $quantityOut;

        $movementData['previous_quantity'] = $previousBalance;
        $movementData['new_quantity'] = $newBalance;
        $movementData['balance_quantity'] = $newBalance;

        $movement = InventoryMovement::create($movementData);

        // Recalculate all movements AFTER this one
        $this->recalculateSubsequentMovements($warehouseId, $productId, $movement);

        return $movement;
    }

    /**
     * Recalculate balance_quantity, previous_quantity, and new_quantity
     * for all movements after the given movement, in chronological order.
     */
    public function recalculateSubsequentMovements(int $warehouseId, int $productId, InventoryMovement $afterMovement): int
    {
        $subsequentMovements = InventoryMovement::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNotNull('balance_quantity')
            ->where('id', '!=', $afterMovement->id)
            ->where(function ($query) use ($afterMovement) {
                $query->where('movement_date', '>', $afterMovement->movement_date)
                    ->orWhere(function ($q) use ($afterMovement) {
                        $q->where('movement_date', '=', $afterMovement->movement_date)
                            ->where('id', '>', $afterMovement->id);
                    });
            })
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($subsequentMovements->isEmpty()) {
            return 0;
        }

        $runningBalance = (float) $afterMovement->balance_quantity;
        $updated = 0;

        foreach ($subsequentMovements as $movement) {
            $quantityIn = (float) $movement->quantity_in;
            $quantityOut = (float) $movement->quantity_out;
            $previousBalance = $runningBalance;
            $newBalance = $previousBalance + $quantityIn - $quantityOut;

            $movement->updateQuietly([
                'previous_quantity' => $previousBalance,
                'new_quantity' => $newBalance,
                'balance_quantity' => $newBalance,
            ]);

            $runningBalance = $newBalance;
            $updated++;
        }

        return $updated;
    }

    /**
     * Recalculate the entire kardex for a product in a specific warehouse.
     * This fixes all existing balance inconsistencies.
     */
    public function recalculateKardex(int $warehouseId, int $productId): int
    {
        $movements = InventoryMovement::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNotNull('balance_quantity')
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($movements->isEmpty()) {
            return 0;
        }

        $runningBalance = 0;
        $updated = 0;

        foreach ($movements as $movement) {
            $quantityIn = (float) $movement->quantity_in;
            $quantityOut = (float) $movement->quantity_out;
            $previousBalance = $runningBalance;
            $newBalance = $previousBalance + $quantityIn - $quantityOut;

            $needsUpdate = round((float) $movement->balance_quantity, 5) !== round($newBalance, 5)
                || round((float) $movement->previous_quantity, 5) !== round($previousBalance, 5)
                || round((float) $movement->new_quantity, 5) !== round($newBalance, 5);

            if ($needsUpdate) {
                $movement->updateQuietly([
                    'previous_quantity' => $previousBalance,
                    'new_quantity' => $newBalance,
                    'balance_quantity' => $newBalance,
                ]);
                $updated++;
            }

            $runningBalance = $newBalance;
        }

        return $updated;
    }

    /**
     * Recalculate kardex for ALL product/warehouse combinations in a company.
     *
     * @return array{total_combinations: int, total_fixed: int, details: array<int, array{warehouse_id: int, product_id: int, fixed: int}>}
     */
    public function recalculateAllKardex(?int $companyId = null, ?int $warehouseId = null): array
    {
        $query = InventoryMovement::query()
            ->whereNotNull('balance_quantity')
            ->select('warehouse_id', 'product_id')
            ->distinct();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $combinations = $query->get();

        $totalFixed = 0;
        $details = [];

        foreach ($combinations as $combo) {
            $fixed = $this->recalculateKardex($combo->warehouse_id, $combo->product_id);
            if ($fixed > 0) {
                $details[] = [
                    'warehouse_id' => $combo->warehouse_id,
                    'product_id' => $combo->product_id,
                    'fixed' => $fixed,
                ];
                $totalFixed += $fixed;
            }
        }

        return [
            'total_combinations' => $combinations->count(),
            'total_fixed' => $totalFixed,
            'details' => $details,
        ];
    }
}
