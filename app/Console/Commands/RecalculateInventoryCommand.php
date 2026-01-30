<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Console\Command;

class RecalculateInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:recalculate
                            {--warehouse= : Recalculate only for a specific warehouse ID}
                            {--product= : Recalculate only for a specific product ID}
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate inventory quantities based on inventory movements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $warehouseId = $this->option('warehouse');
        $productId = $this->option('product');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se harán cambios en la base de datos.');
            $this->newLine();
        }

        $this->info('Recalculando inventario basado en movimientos...');
        $this->newLine();

        // Get all unique product + warehouse combinations from movements
        $query = InventoryMovement::query()
            ->select('product_id', 'warehouse_id', 'company_id')
            ->whereNotNull('product_id')
            ->whereNotNull('warehouse_id');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
            $this->info("Filtrando por bodega ID: {$warehouseId}");
        }

        if ($productId) {
            $query->where('product_id', $productId);
            $this->info("Filtrando por producto ID: {$productId}");
        }

        $combinations = $query->groupBy('product_id', 'warehouse_id', 'company_id')->get();

        if ($combinations->isEmpty()) {
            $this->warn('No se encontraron movimientos de inventario.');

            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$combinations->count()} combinaciones de producto/bodega.");
        $this->newLine();

        $updated = 0;
        $created = 0;
        $unchanged = 0;
        $differences = [];

        $progressBar = $this->output->createProgressBar($combinations->count());
        $progressBar->start();

        foreach ($combinations as $combo) {
            // Calculate totals from movements
            $totals = InventoryMovement::where('product_id', $combo->product_id)
                ->where('warehouse_id', $combo->warehouse_id)
                ->selectRaw('COALESCE(SUM(quantity_in), 0) as total_in')
                ->selectRaw('COALESCE(SUM(quantity_out), 0) as total_out')
                ->first();

            $calculatedQuantity = $totals->total_in - $totals->total_out;

            // Get current inventory record
            $inventory = Inventory::where('product_id', $combo->product_id)
                ->where('warehouse_id', $combo->warehouse_id)
                ->first();

            $currentQuantity = $inventory ? $inventory->quantity : null;

            // Check if there's a difference
            if ($inventory && abs($inventory->quantity - $calculatedQuantity) < 0.00001) {
                $unchanged++;
                $progressBar->advance();

                continue;
            }

            // Record the difference for reporting
            $differences[] = [
                'product_id' => $combo->product_id,
                'warehouse_id' => $combo->warehouse_id,
                'current' => $currentQuantity ?? 'N/A',
                'calculated' => $calculatedQuantity,
                'difference' => $currentQuantity !== null ? ($currentQuantity - $calculatedQuantity) : 'NEW',
            ];

            if (! $dryRun) {
                if ($inventory) {
                    $inventory->quantity = $calculatedQuantity;
                    $inventory->available_quantity = $calculatedQuantity - ($inventory->reserved_quantity ?? 0);
                    $inventory->save();
                    $updated++;
                } else {
                    Inventory::create([
                        'company_id' => $combo->company_id,
                        'product_id' => $combo->product_id,
                        'warehouse_id' => $combo->warehouse_id,
                        'quantity' => $calculatedQuantity,
                        'available_quantity' => $calculatedQuantity,
                        'reserved_quantity' => 0,
                        'is_active' => true,
                        'active_at' => now(),
                    ]);
                    $created++;
                }
            } else {
                if ($inventory) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show differences table if any
        if (! empty($differences)) {
            $this->warn('Diferencias encontradas:');
            $this->newLine();

            $this->table(
                ['Producto ID', 'Bodega ID', 'Cantidad Actual', 'Cantidad Calculada', 'Diferencia'],
                collect($differences)->map(function ($diff) {
                    return [
                        $diff['product_id'],
                        $diff['warehouse_id'],
                        is_numeric($diff['current']) ? number_format($diff['current'], 5) : $diff['current'],
                        number_format($diff['calculated'], 5),
                        is_numeric($diff['difference']) ? number_format($diff['difference'], 5) : $diff['difference'],
                    ];
                })->toArray()
            );
            $this->newLine();
        }

        // Summary
        $this->info('Resumen:');
        $this->line("  - Sin cambios: {$unchanged}");
        $this->line("  - Actualizados: {$updated}");
        $this->line("  - Creados: {$created}");

        if ($dryRun && ($updated > 0 || $created > 0)) {
            $this->newLine();
            $this->warn('Ejecuta sin --dry-run para aplicar los cambios.');
        }

        $this->newLine();
        $this->info('Proceso completado.');

        return Command::SUCCESS;
    }
}
