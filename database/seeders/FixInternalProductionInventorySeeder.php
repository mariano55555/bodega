<?php

namespace Database\Seeders;

use App\Models\InternalProduction;
use App\Models\Inventory;
use Illuminate\Database\Seeder;

class FixInternalProductionInventorySeeder extends Seeder
{
    /**
     * Fix inventory records for completed internal productions that are missing.
     * This seeder corrects a bug where internal productions were completed
     * but Inventory records were not created.
     */
    public function run(): void
    {
        $this->command->info('Buscando producciones internas completadas sin registros de inventario...');

        $completedProductions = InternalProduction::where('status', 'completado')
            ->with('details.product')
            ->get();

        $fixed = 0;
        $skipped = 0;

        foreach ($completedProductions as $production) {
            foreach ($production->details as $detail) {
                // Check if inventory record exists
                $existingInventory = Inventory::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $production->warehouse_id)
                    ->first();

                if (! $existingInventory) {
                    // Create inventory record
                    Inventory::create([
                        'product_id' => $detail->product_id,
                        'warehouse_id' => $production->warehouse_id,
                        'quantity' => $detail->quantity,
                        'available_quantity' => $detail->quantity,
                        'unit_cost' => $detail->unit_price,
                        'is_active' => true,
                        'active_at' => now(),
                    ]);

                    $productName = $detail->product->name ?? "ID: {$detail->product_id}";
                    $this->command->info("  ✓ Creado: {$productName} - Cantidad: {$detail->quantity}");
                    $fixed++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->command->newLine();
        $this->command->info('Resumen:');
        $this->command->info("  - Registros creados: {$fixed}");
        $this->command->info("  - Ya existían: {$skipped}");
    }
}
