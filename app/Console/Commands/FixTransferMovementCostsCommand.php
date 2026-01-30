<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use Illuminate\Console\Command;

class FixTransferMovementCostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-transfer-costs
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix transfer inventory movements that have unit_cost = 0 by getting the cost from inventory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se harán cambios en la base de datos.');
            $this->newLine();
        }

        $this->info('Buscando movimientos de transferencia con costo = 0...');
        $this->newLine();

        // Get transfer movement reasons
        $transferOut = MovementReason::where('code', 'TRANSFER_OUT')->first();
        $transferIn = MovementReason::where('code', 'TRANSFER_IN')->first();

        if (! $transferOut && ! $transferIn) {
            $this->error('No se encontraron los motivos de movimiento de transferencia (TRANSFER_OUT, TRANSFER_IN).');

            return Command::FAILURE;
        }

        $reasonIds = collect([$transferOut?->id, $transferIn?->id])->filter()->values()->toArray();

        if (empty($reasonIds)) {
            $this->error('No se encontraron IDs de motivos de transferencia.');

            return Command::FAILURE;
        }

        $this->info('Motivos de transferencia encontrados:');
        if ($transferOut) {
            $this->line("  - TRANSFER_OUT: ID {$transferOut->id} - {$transferOut->name}");
        }
        if ($transferIn) {
            $this->line("  - TRANSFER_IN: ID {$transferIn->id} - {$transferIn->name}");
        }
        $this->newLine();

        // Find affected movements (transfer movements with unit_cost = 0 or null)
        $affectedMovements = InventoryMovement::whereIn('movement_reason_id', $reasonIds)
            ->where(function ($q) {
                $q->whereNull('unit_cost')
                    ->orWhere('unit_cost', 0);
            })
            ->whereNotNull('transfer_id')
            ->with(['transfer', 'product'])
            ->get();

        $count = $affectedMovements->count();

        if ($count === 0) {
            $this->info('No se encontraron movimientos de transferencia con costo = 0.');

            return Command::SUCCESS;
        }

        $this->warn("Se encontraron {$count} movimientos de transferencia con costo = 0.");
        $this->newLine();

        // Show sample of affected movements
        $sample = $affectedMovements->take(10);
        $this->info('Muestra de movimientos afectados:');
        $this->table(
            ['ID', 'Transfer ID', 'Product', 'Warehouse ID', 'Quantity', 'Current Cost', 'Movement Date'],
            $sample->map(fn ($m) => [
                $m->id,
                $m->transfer_id,
                $m->product?->name ?? 'N/A',
                $m->warehouse_id,
                $m->quantity_in > 0 ? $m->quantity_in : $m->quantity_out,
                number_format($m->unit_cost ?? 0, 4),
                $m->movement_date?->format('Y-m-d'),
            ])->toArray()
        );

        if ($count > 10) {
            $remaining = $count - 10;
            $this->line("... y {$remaining} más.");
        }
        $this->newLine();

        if ($dryRun) {
            $this->info('Calculando costos que se asignarían...');
            $this->newLine();

            $updates = [];
            foreach ($affectedMovements as $movement) {
                // For TRANSFER_IN, get cost from source warehouse (from_warehouse_id)
                // For TRANSFER_OUT, use the movement's warehouse (which is the source)
                $warehouseId = $movement->warehouse_id;
                if ($movement->movement_reason_id === $transferIn?->id && $movement->transfer) {
                    $warehouseId = $movement->transfer->from_warehouse_id;
                }

                $inventory = Inventory::where('product_id', $movement->product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                $unitCost = $inventory->unit_cost ?? 0;

                // If still no cost, try to get from any warehouse
                if ($unitCost == 0) {
                    $anyInventory = Inventory::where('product_id', $movement->product_id)
                        ->whereNotNull('unit_cost')
                        ->where('unit_cost', '>', 0)
                        ->first();
                    $unitCost = $anyInventory->unit_cost ?? 0;
                }

                $quantity = $movement->quantity_in > 0 ? $movement->quantity_in : $movement->quantity_out;
                $totalCost = $unitCost * $quantity;

                $updates[] = [
                    'id' => $movement->id,
                    'product' => $movement->product?->name ?? 'N/A',
                    'current_cost' => number_format($movement->unit_cost ?? 0, 4),
                    'new_cost' => number_format($unitCost, 4),
                    'quantity' => number_format($quantity, 2),
                    'new_total' => number_format($totalCost, 2),
                ];
            }

            $this->table(
                ['ID', 'Producto', 'Costo Actual', 'Nuevo Costo', 'Cantidad', 'Nuevo Total'],
                array_slice($updates, 0, 20)
            );

            if (count($updates) > 20) {
                $this->line('... mostrando solo los primeros 20 registros.');
            }

            $this->newLine();
            $this->warn("Se actualizarían {$count} movimientos con el costo del inventario.");
            $this->newLine();
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (! $this->confirm("¿Deseas actualizar {$count} movimientos de transferencia con el costo del inventario?")) {
            $this->info('Operación cancelada.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Actualizando movimientos...');

        $updated = 0;
        $skipped = 0;
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        foreach ($affectedMovements as $movement) {
            // For TRANSFER_IN, get cost from source warehouse (from_warehouse_id)
            // For TRANSFER_OUT, use the movement's warehouse (which is the source)
            $warehouseId = $movement->warehouse_id;
            if ($movement->movement_reason_id === $transferIn?->id && $movement->transfer) {
                $warehouseId = $movement->transfer->from_warehouse_id;
            }

            $inventory = Inventory::where('product_id', $movement->product_id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            $unitCost = $inventory->unit_cost ?? 0;

            // If still no cost, try to get from any warehouse
            if ($unitCost == 0) {
                $anyInventory = Inventory::where('product_id', $movement->product_id)
                    ->whereNotNull('unit_cost')
                    ->where('unit_cost', '>', 0)
                    ->first();
                $unitCost = $anyInventory->unit_cost ?? 0;
            }

            if ($unitCost > 0) {
                $quantity = $movement->quantity_in > 0 ? $movement->quantity_in : $movement->quantity_out;

                $movement->update([
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $quantity,
                ]);
                $updated++;
            } else {
                $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Se actualizaron {$updated} movimientos correctamente.");
        if ($skipped > 0) {
            $this->warn("⚠ Se omitieron {$skipped} movimientos (no se encontró costo en inventario).");
        }
        $this->newLine();

        return Command::SUCCESS;
    }
}
