<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMovementDatesCommand extends Command
{
    protected $signature = 'inventory:fix-movement-dates
                            {--dry-run : Mostrar cambios sin aplicar}
                            {--warehouse= : Filtrar por bodega ID}';

    protected $description = 'Corrige movement_date de movimientos usando document_date del despacho/traslado cuando existe';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $warehouseId = $this->option('warehouse');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se haran cambios en la base de datos.');
            $this->newLine();
        }

        // 1. Show affected dispatch movements
        $dispatchQuery = DB::table('inventory_movements as im')
            ->join('dispatches as d', 'd.id', '=', 'im.dispatch_id')
            ->whereNotNull('d.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(d.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId));

        $dispatchCount = $dispatchQuery->count();

        $this->info("Movimientos de despachos con fecha incorrecta: {$dispatchCount}");

        if ($dispatchCount > 0) {
            $sample = DB::table('inventory_movements as im')
                ->join('dispatches as d', 'd.id', '=', 'im.dispatch_id')
                ->join('products as p', 'p.id', '=', 'im.product_id')
                ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                ->whereNotNull('d.document_date')
                ->whereRaw('DATE(im.movement_date) != DATE(d.document_date)')
                ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
                ->select([
                    'im.id',
                    'w.name as bodega',
                    'p.name as producto',
                    'd.dispatch_number',
                    'im.movement_date as fecha_actual',
                    'd.document_date as fecha_documento',
                ])
                ->limit(20)
                ->get();

            $this->table(
                ['ID', 'Bodega', 'Producto', 'Despacho', 'Fecha Actual', 'Fecha Documento'],
                $sample->map(fn ($row) => [
                    $row->id,
                    mb_substr($row->bodega, 0, 20),
                    mb_substr($row->producto, 0, 25),
                    $row->dispatch_number,
                    $row->fecha_actual,
                    $row->fecha_documento,
                ])->toArray()
            );

            if ($dispatchCount > 20) {
                $this->line("... mostrando solo 20 de {$dispatchCount} registros.");
            }
        }

        $this->newLine();

        // 2. Show affected transfer movements
        $transferQuery = DB::table('inventory_movements as im')
            ->join('inventory_transfers as t', 't.id', '=', 'im.transfer_id')
            ->whereNotNull('t.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(t.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId));

        $transferCount = $transferQuery->count();

        $this->info("Movimientos de traslados con fecha incorrecta: {$transferCount}");

        if ($transferCount > 0) {
            $sample = DB::table('inventory_movements as im')
                ->join('inventory_transfers as t', 't.id', '=', 'im.transfer_id')
                ->join('products as p', 'p.id', '=', 'im.product_id')
                ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                ->whereNotNull('t.document_date')
                ->whereRaw('DATE(im.movement_date) != DATE(t.document_date)')
                ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
                ->select([
                    'im.id',
                    'w.name as bodega',
                    'p.name as producto',
                    't.transfer_number',
                    'im.movement_date as fecha_actual',
                    't.document_date as fecha_documento',
                ])
                ->limit(20)
                ->get();

            $this->table(
                ['ID', 'Bodega', 'Producto', 'Traslado', 'Fecha Actual', 'Fecha Documento'],
                $sample->map(fn ($row) => [
                    $row->id,
                    mb_substr($row->bodega, 0, 20),
                    mb_substr($row->producto, 0, 25),
                    $row->transfer_number,
                    $row->fecha_actual,
                    $row->fecha_documento,
                ])->toArray()
            );

            if ($transferCount > 20) {
                $this->line("... mostrando solo 20 de {$transferCount} registros.");
            }
        }

        $this->newLine();

        $totalAffected = $dispatchCount + $transferCount;

        if ($totalAffected === 0) {
            $this->info('No hay movimientos que corregir. Todas las fechas estan correctas.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Total de movimientos a corregir: {$totalAffected}");
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        if (! $this->confirm("Se corregiran {$totalAffected} movimientos y se recalcularan saldos. Continuar?")) {
            $this->info('Operacion cancelada.');

            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // 3. Update dispatch movement dates
            if ($dispatchCount > 0) {
                $updated = DB::update('
                    UPDATE inventory_movements im
                    JOIN dispatches d ON d.id = im.dispatch_id
                    SET im.movement_date = d.document_date
                    WHERE d.document_date IS NOT NULL
                    AND DATE(im.movement_date) != DATE(d.document_date)
                    '.($warehouseId ? "AND im.warehouse_id = {$warehouseId}" : '').'
                ');
                $this->info("Movimientos de despachos corregidos: {$updated}");
            }

            // 4. Update transfer movement dates
            if ($transferCount > 0) {
                $updated = DB::update('
                    UPDATE inventory_movements im
                    JOIN inventory_transfers t ON t.id = im.transfer_id
                    SET im.movement_date = t.document_date
                    WHERE t.document_date IS NOT NULL
                    AND DATE(im.movement_date) != DATE(t.document_date)
                    '.($warehouseId ? "AND im.warehouse_id = {$warehouseId}" : '').'
                ');
                $this->info("Movimientos de traslados corregidos: {$updated}");
            }

            // 5. Recalculate balances for affected product/warehouse combinations
            $this->newLine();
            $this->info('Recalculando saldos...');

            $combinations = InventoryMovement::query()
                ->where(function ($q) {
                    $q->whereNotNull('dispatch_id')->orWhereNotNull('transfer_id');
                })
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->selectRaw('DISTINCT product_id, warehouse_id')
                ->get();

            $bar = $this->output->createProgressBar($combinations->count());
            $bar->start();

            foreach ($combinations as $combo) {
                $this->recalculateBalances($combo->product_id, $combo->warehouse_id);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info("Proceso completado. Se recalcularon saldos de {$combinations->count()} combinaciones producto/bodega.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");

            return Command::FAILURE;
        }
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
}
