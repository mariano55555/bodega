<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Services\InventoryValuationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Corrects the current weighted average cost stored in inventory.
 *
 * Before October 2026 the inventory job added the incoming quantity to the stock before
 * averaging, so each inbound movement was counted twice and the stored average drifted.
 * This command rebuilds the average from the kardex and fixes only the inventory row:
 * movements, their recorded costs and closed months are left untouched.
 */
class RecalculateAverageCostCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'inventory:recalculate-average-cost
                            {--product= : Only this product id}
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * @var string
     */
    protected $description = 'Recalcula el costo promedio actual del inventario a partir del kardex, sin modificar el historial';

    public function handle(InventoryValuationService $valuationService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $productId = $this->option('product') ? (int) $this->option('product') : null;

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se harán cambios en la base de datos.');
        }

        $balances = $valuationService->replayAverageCosts($productId);

        $changes = [];
        $skipped = [];

        Inventory::query()
            ->with(['product:id,name', 'warehouse:id,name'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->where('quantity', '>', 0)
            ->orderBy('product_id')
            ->orderBy('warehouse_id')
            ->each(function (Inventory $inventory) use ($balances, &$changes, &$skipped): void {
                $balance = $balances[$inventory->product_id.'-'.$inventory->warehouse_id] ?? null;

                if (! $balance || abs($balance['quantity'] - (float) $inventory->quantity) > 0.001) {
                    $skipped[] = $inventory;

                    return;
                }

                if (abs($balance['unit_cost'] - (float) $inventory->unit_cost) > 0.0001) {
                    $changes[] = ['inventory' => $inventory, 'unit_cost' => $balance['unit_cost']];
                }
            });

        $this->table(
            ['Producto', 'Bodega', 'Existencia', 'Costo actual', 'Costo correcto', 'Diferencia de valor'],
            collect($changes)->map(fn (array $change): array => [
                $change['inventory']->product?->name,
                $change['inventory']->warehouse?->name,
                (float) $change['inventory']->quantity,
                (float) $change['inventory']->unit_cost,
                $change['unit_cost'],
                round((float) $change['inventory']->quantity * ($change['unit_cost'] - (float) $change['inventory']->unit_cost), 2),
            ])->all()
        );

        $this->info(count($changes).' registros de inventario con costo promedio incorrecto.');

        foreach ($skipped as $inventory) {
            $this->warn("Omitido: {$inventory->product?->name} en {$inventory->warehouse?->name}: la existencia no coincide con el kardex.");
        }

        if ($dryRun || $changes === []) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes): void {
            foreach ($changes as $change) {
                $inventory = $change['inventory'];

                Log::info('Costo promedio recalculado', [
                    'inventory_id' => $inventory->id,
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'old_unit_cost' => (float) $inventory->unit_cost,
                    'new_unit_cost' => $change['unit_cost'],
                ]);

                $inventory->unit_cost = $change['unit_cost'];
                $inventory->total_value = round((float) $inventory->quantity * $change['unit_cost'], 5);
                $inventory->save();
            }
        });

        $this->info('Costos promedio actualizados. Los movimientos del kardex no se modificaron.');

        return self::SUCCESS;
    }
}
