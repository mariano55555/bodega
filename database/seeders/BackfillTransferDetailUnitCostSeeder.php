<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryTransferDetail;
use Illuminate\Database\Seeder;

class BackfillTransferDetailUnitCostSeeder extends Seeder
{
    /**
     * Backfill unit_cost on existing transfer details from source warehouse inventory.
     */
    public function run(): void
    {
        $this->command->info('Actualizando unit_cost en detalles de traslados existentes...');

        $details = InventoryTransferDetail::whereNull('unit_cost')
            ->with('transfer')
            ->get();

        $updated = 0;

        foreach ($details as $detail) {
            $inventory = Inventory::where('product_id', $detail->product_id)
                ->where('warehouse_id', $detail->transfer->from_warehouse_id)
                ->first();

            if ($inventory && $inventory->unit_cost) {
                $detail->unit_cost = $inventory->unit_cost;
                $detail->save();
                $updated++;
            }
        }

        $this->command->info("Actualizados: {$updated} de {$details->count()} registros.");
    }
}
