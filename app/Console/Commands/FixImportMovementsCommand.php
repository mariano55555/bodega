<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixImportMovementsCommand extends Command
{
    protected $signature = 'inventory:fix-import-movements
                            {--dry-run : Mostrar cambios sin aplicar}
                            {--warehouse= : Filtrar por bodega ID}
                            {--product= : Filtrar por producto ID}
                            {--date=2026-01-01 : Fecha del inventario inicial consolidado}';

    protected $description = 'Corrige los movimientos de importacion consolidando INI/ENT/SAL en un solo movimiento inicial';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $warehouseId = $this->option('warehouse');
        $productId = $this->option('product');
        $consolidatedDate = $this->option('date');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se haran cambios en la base de datos.');
            $this->newLine();
        }

        $this->info('Buscando movimientos de importacion a corregir...');
        $this->newLine();

        // 1. Validate INITIAL_STOCK reason exists
        $initialStockReason = MovementReason::where('code', 'INITIAL_STOCK')->first();
        if (! $initialStockReason) {
            $this->error('No se encontro la razon de movimiento INITIAL_STOCK');

            return Command::FAILURE;
        }

        // 2. Find all import movements (INI, ENT, SAL patterns)
        $affectedMovements = InventoryMovement::query()
            ->where(function ($query) {
                $query->where('reference_number', 'like', 'IMP-DIC25-P%-INI')
                    ->orWhere('reference_number', 'like', 'IMP-DIC25-P%-ENT')
                    ->orWhere('reference_number', 'like', 'IMP-DIC25-P%-SAL');
            })
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->with(['product:id,name,sku', 'warehouse:id,name,code'])
            ->get();

        if ($affectedMovements->isEmpty()) {
            $this->info('No se encontraron movimientos de importacion a corregir.');

            return Command::SUCCESS;
        }

        // 3. Group by product-warehouse
        $groupedMovements = $affectedMovements->groupBy(function ($movement) {
            return $movement->product_id.'-'.$movement->warehouse_id;
        });

        $this->info("Se encontraron {$affectedMovements->count()} movimientos en {$groupedMovements->count()} combinaciones producto/bodega.");
        $this->newLine();

        // 4. Calculate consolidated values and show preview
        $consolidationData = [];

        foreach ($groupedMovements as $key => $movements) {
            [$productId, $warehouseId] = explode('-', $key);

            // Sort movements by date and id to find the LAST one
            $sortedMovements = $movements->sortBy([
                ['movement_date', 'asc'],
                ['id', 'asc'],
            ]);

            $lastMovement = $sortedMovements->last();
            $firstMovement = $sortedMovements->first();

            // Get the balance_quantity from the LAST movement - this is the correct final balance
            $consolidatedQty = $lastMovement->balance_quantity ?? 0;

            // Get unit cost from the first movement (INI) or last movement
            $unitCost = $firstMovement->unit_cost ?? $lastMovement->unit_cost ?? 0;

            // If no cost found, try from Inventory
            if ($unitCost == 0) {
                $inventory = Inventory::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();
                $unitCost = $inventory?->unit_cost ?? 0;
            }

            // For display purposes, get individual quantities
            $iniMovement = $movements->first(fn ($m) => str_ends_with($m->reference_number, '-INI'));
            $entMovement = $movements->first(fn ($m) => str_ends_with($m->reference_number, '-ENT'));
            $salMovement = $movements->first(fn ($m) => str_ends_with($m->reference_number, '-SAL'));

            $consolidationData[] = [
                'key' => $key,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'product_name' => $movements->first()->product?->name ?? 'N/A',
                'product_sku' => $movements->first()->product?->sku ?? 'N/A',
                'warehouse_name' => $movements->first()->warehouse?->name ?? 'N/A',
                'company_id' => $movements->first()->company_id,
                'initial_qty' => $iniMovement?->quantity_in ?? 0,
                'entries_qty' => $entMovement?->quantity_in ?? 0,
                'exits_qty' => $salMovement?->quantity_out ?? 0,
                'consolidated_qty' => $consolidatedQty,
                'unit_cost' => $unitCost,
                'movements_to_delete' => $movements->pluck('id')->toArray(),
            ];
        }

        // Show preview table
        $this->table(
            ['Producto', 'SKU', 'Bodega', 'INI', 'ENT', 'SAL', 'Consolidado', 'Costo'],
            collect($consolidationData)->map(fn ($data) => [
                substr($data['product_name'], 0, 30),
                $data['product_sku'],
                $data['warehouse_name'],
                number_format($data['initial_qty'], 2),
                number_format($data['entries_qty'], 2),
                number_format($data['exits_qty'], 2),
                number_format($data['consolidated_qty'], 2),
                number_format($data['unit_cost'], 4),
            ])->take(30)->toArray()
        );

        if (count($consolidationData) > 30) {
            $this->line('... mostrando solo los primeros 30 registros.');
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("Se consolidarian {$groupedMovements->count()} combinaciones producto/bodega.");
            $this->warn("Se eliminarian {$affectedMovements->count()} movimientos de importacion.");
            $this->newLine();
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        // 5. Confirm before proceeding
        if (! $this->confirm("Proceder con la correccion de {$groupedMovements->count()} combinaciones producto/bodega?")) {
            $this->info('Operacion cancelada.');

            return Command::SUCCESS;
        }

        // 6. Process in transaction
        $this->info('Procesando correcciones...');
        $progressBar = $this->output->createProgressBar(count($consolidationData));
        $progressBar->start();

        $created = 0;
        $deleted = 0;
        $recalculated = 0;

        DB::transaction(function () use ($consolidationData, $initialStockReason, $consolidatedDate, &$created, &$deleted, &$recalculated, $progressBar) {
            foreach ($consolidationData as $data) {
                // Create consolidated movement
                $newMovement = InventoryMovement::create([
                    'company_id' => $data['company_id'],
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'movement_reason_id' => $initialStockReason->id,
                    'movement_type' => 'adjustment',
                    'movement_date' => $consolidatedDate,
                    'quantity' => $data['consolidated_qty'],
                    'quantity_in' => $data['consolidated_qty'],
                    'quantity_out' => 0,
                    'balance_quantity' => $data['consolidated_qty'],
                    'previous_quantity' => 0,
                    'new_quantity' => $data['consolidated_qty'],
                    'unit_cost' => $data['unit_cost'],
                    'total_cost' => $data['consolidated_qty'] * $data['unit_cost'],
                    'reference_number' => 'IMP-FIX-P'.$data['product_id'].'-INIT',
                    'notes' => sprintf(
                        'Inventario inicial consolidado - Correccion de importacion DIC25. Valores originales: INI=%.2f, ENT=%.2f, SAL=%.2f',
                        $data['initial_qty'],
                        $data['entries_qty'],
                        $data['exits_qty']
                    ),
                    'status' => 'completed',
                    'is_confirmed' => true,
                    'confirmed_at' => now(),
                    'completed_at' => now(),
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => \App\Models\User::first()?->id,
                ]);
                $created++;

                // Soft delete original movements
                foreach ($data['movements_to_delete'] as $movementId) {
                    $movement = InventoryMovement::find($movementId);
                    if ($movement) {
                        $movement->notes = ($movement->notes ?? '').' [REEMPLAZADO por consolidacion ID:'.$newMovement->id.']';
                        $movement->save();
                        $movement->delete();
                        $deleted++;
                    }
                }

                // Recalculate balance_quantity for all movements of this product-warehouse
                $this->recalculateBalances($data['product_id'], $data['warehouse_id']);
                $recalculated++;

                // Update Inventory table
                $this->updateInventoryRecord($data['product_id'], $data['warehouse_id'], $data['company_id']);

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // 7. Summary
        $this->info('Resumen:');
        $this->line("  - Movimientos consolidados creados: {$created}");
        $this->line("  - Movimientos originales eliminados: {$deleted}");
        $this->line("  - Productos recalculados: {$recalculated}");
        $this->newLine();
        $this->info('Correccion completada exitosamente.');

        return Command::SUCCESS;
    }

    private function recalculateBalances(int $productId, int $warehouseId): void
    {
        $movements = InventoryMovement::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;

        foreach ($movements as $movement) {
            $previousBalance = $runningBalance;
            $runningBalance += ($movement->quantity_in ?? 0) - ($movement->quantity_out ?? 0);

            $movement->update([
                'previous_quantity' => $previousBalance,
                'balance_quantity' => $runningBalance,
                'new_quantity' => $runningBalance,
            ]);
        }
    }

    private function updateInventoryRecord(int $productId, int $warehouseId, int $companyId): void
    {
        $totals = InventoryMovement::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('COALESCE(SUM(quantity_in), 0) as total_in')
            ->selectRaw('COALESCE(SUM(quantity_out), 0) as total_out')
            ->first();

        $calculatedQuantity = $totals->total_in - $totals->total_out;

        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($inventory) {
            $inventory->update([
                'quantity' => $calculatedQuantity,
                'available_quantity' => $calculatedQuantity - ($inventory->reserved_quantity ?? 0),
            ]);
        } else {
            Inventory::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $calculatedQuantity,
                'available_quantity' => $calculatedQuantity,
                'reserved_quantity' => 0,
                'is_active' => true,
                'active_at' => now(),
            ]);
        }
    }
}
