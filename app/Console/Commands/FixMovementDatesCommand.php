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

    protected $description = 'Corrige movement_date de movimientos usando document_date del despacho/traslado/produccion interna cuando existe';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $warehouseId = $this->option('warehouse');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se haran cambios en la base de datos.');
            $this->newLine();
        }

        // 1. Show affected purchase movements
        $purchaseCount = $this->showAffectedPurchases($warehouseId);

        // 2. Show affected dispatch movements
        $dispatchCount = $this->showAffectedDispatches($warehouseId);

        // 3. Show affected transfer movements
        $transferCount = $this->showAffectedTransfers($warehouseId);

        // 4. Show affected donation movements
        $donationCount = $this->showAffectedDonations($warehouseId);

        // 5. Show affected internal production movements
        $productionCount = $this->showAffectedProductions($warehouseId);

        $totalAffected = $purchaseCount + $dispatchCount + $transferCount + $donationCount + $productionCount;

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
            // 4. Update purchase movement dates
            if ($purchaseCount > 0) {
                $updated = DB::update('
                    UPDATE inventory_movements im
                    JOIN purchases p ON p.id = im.purchase_id
                    SET im.movement_date = p.document_date
                    WHERE p.document_date IS NOT NULL
                    AND DATE(im.movement_date) != DATE(p.document_date)
                    '.($warehouseId ? "AND im.warehouse_id = {$warehouseId}" : '').'
                ');
                $this->info("Movimientos de compras corregidos: {$updated}");
            }

            // 5. Update dispatch movement dates
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

            // 6. Update transfer movement dates
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

            // 7. Update donation movement dates
            if ($donationCount > 0) {
                $updated = DB::update('
                    UPDATE inventory_movements im
                    JOIN donations d ON d.id = im.donation_id
                    SET im.movement_date = d.document_date
                    WHERE d.document_date IS NOT NULL
                    AND DATE(im.movement_date) != DATE(d.document_date)
                    '.($warehouseId ? "AND im.warehouse_id = {$warehouseId}" : '').'
                ');
                $this->info("Movimientos de donaciones corregidos: {$updated}");
            }

            // 8. Update internal production movement dates
            if ($productionCount > 0) {
                $updated = DB::update('
                    UPDATE inventory_movements im
                    JOIN internal_productions ip ON ip.production_number = im.document_number
                    SET im.movement_date = ip.document_date
                    WHERE im.movement_type = ?
                    AND ip.document_date IS NOT NULL
                    AND DATE(im.movement_date) != DATE(ip.document_date)
                    '.($warehouseId ? "AND im.warehouse_id = {$warehouseId}" : '').'
                ', ['production']);
                $this->info("Movimientos de produccion interna corregidos: {$updated}");
            }

            // 8. Recalculate balances for affected product/warehouse combinations
            $this->newLine();
            $this->info('Recalculando saldos...');

            $combinations = InventoryMovement::query()
                ->where(function ($q) {
                    $q->whereNotNull('purchase_id')
                        ->orWhereNotNull('dispatch_id')
                        ->orWhereNotNull('transfer_id')
                        ->orWhereNotNull('donation_id')
                        ->orWhere('movement_type', 'production');
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

    private function showAffectedPurchases(?string $warehouseId): int
    {
        $count = DB::table('inventory_movements as im')
            ->join('purchases as p', 'p.id', '=', 'im.purchase_id')
            ->whereNotNull('p.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(p.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
            ->count();

        $this->info("Movimientos de compras con fecha incorrecta: {$count}");

        if ($count > 0) {
            $sample = DB::table('inventory_movements as im')
                ->join('purchases as p', 'p.id', '=', 'im.purchase_id')
                ->join('products as p2', 'p2.id', '=', 'im.product_id')
                ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                ->whereNotNull('p.document_date')
                ->whereRaw('DATE(im.movement_date) != DATE(p.document_date)')
                ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
                ->select([
                    'im.id',
                    'w.name as bodega',
                    'p2.name as producto',
                    'p.purchase_number',
                    'im.movement_date as fecha_actual',
                    'p.document_date as fecha_documento',
                ])
                ->limit(20)
                ->get();

            $this->table(
                ['ID', 'Bodega', 'Producto', 'Compra', 'Fecha Actual', 'Fecha Documento'],
                $sample->map(fn ($row) => [
                    $row->id,
                    mb_substr($row->bodega, 0, 20),
                    mb_substr($row->producto, 0, 25),
                    $row->purchase_number,
                    $row->fecha_actual,
                    $row->fecha_documento,
                ])->toArray()
            );

            if ($count > 20) {
                $this->line("... mostrando solo 20 de {$count} registros.");
            }
        }

        $this->newLine();

        return $count;
    }

    private function showAffectedDispatches(?string $warehouseId): int
    {
        $count = DB::table('inventory_movements as im')
            ->join('dispatches as d', 'd.id', '=', 'im.dispatch_id')
            ->whereNotNull('d.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(d.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
            ->count();

        $this->info("Movimientos de despachos con fecha incorrecta: {$count}");

        if ($count > 0) {
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

            if ($count > 20) {
                $this->line("... mostrando solo 20 de {$count} registros.");
            }
        }

        $this->newLine();

        return $count;
    }

    private function showAffectedTransfers(?string $warehouseId): int
    {
        $count = DB::table('inventory_movements as im')
            ->join('inventory_transfers as t', 't.id', '=', 'im.transfer_id')
            ->whereNotNull('t.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(t.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
            ->count();

        $this->info("Movimientos de traslados con fecha incorrecta: {$count}");

        if ($count > 0) {
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

            if ($count > 20) {
                $this->line("... mostrando solo 20 de {$count} registros.");
            }
        }

        $this->newLine();

        return $count;
    }

    private function showAffectedDonations(?string $warehouseId): int
    {
        $count = DB::table('inventory_movements as im')
            ->join('donations as d', 'd.id', '=', 'im.donation_id')
            ->whereNotNull('d.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(d.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
            ->count();

        $this->info("Movimientos de donaciones con fecha incorrecta: {$count}");

        if ($count > 0) {
            $sample = DB::table('inventory_movements as im')
                ->join('donations as d', 'd.id', '=', 'im.donation_id')
                ->join('products as p', 'p.id', '=', 'im.product_id')
                ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                ->whereNotNull('d.document_date')
                ->whereRaw('DATE(im.movement_date) != DATE(d.document_date)')
                ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
                ->select([
                    'im.id',
                    'w.name as bodega',
                    'p.name as producto',
                    'd.donation_number',
                    'im.movement_date as fecha_actual',
                    'd.document_date as fecha_documento',
                ])
                ->limit(20)
                ->get();

            $this->table(
                ['ID', 'Bodega', 'Producto', 'Donacion', 'Fecha Actual', 'Fecha Documento'],
                $sample->map(fn ($row) => [
                    $row->id,
                    mb_substr($row->bodega, 0, 20),
                    mb_substr($row->producto, 0, 25),
                    $row->donation_number,
                    $row->fecha_actual,
                    $row->fecha_documento,
                ])->toArray()
            );

            if ($count > 20) {
                $this->line("... mostrando solo 20 de {$count} registros.");
            }
        }

        $this->newLine();

        return $count;
    }

    private function showAffectedProductions(?string $warehouseId): int
    {
        $count = DB::table('inventory_movements as im')
            ->join('internal_productions as ip', 'ip.production_number', '=', 'im.document_number')
            ->where('im.movement_type', 'production')
            ->whereNotNull('ip.document_date')
            ->whereRaw('DATE(im.movement_date) != DATE(ip.document_date)')
            ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
            ->count();

        $this->info("Movimientos de produccion interna con fecha incorrecta: {$count}");

        if ($count > 0) {
            $sample = DB::table('inventory_movements as im')
                ->join('internal_productions as ip', 'ip.production_number', '=', 'im.document_number')
                ->join('products as p', 'p.id', '=', 'im.product_id')
                ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                ->where('im.movement_type', 'production')
                ->whereNotNull('ip.document_date')
                ->whereRaw('DATE(im.movement_date) != DATE(ip.document_date)')
                ->when($warehouseId, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
                ->select([
                    'im.id',
                    'w.name as bodega',
                    'p.name as producto',
                    'ip.production_number',
                    'im.movement_date as fecha_actual',
                    'ip.document_date as fecha_documento',
                ])
                ->limit(20)
                ->get();

            $this->table(
                ['ID', 'Bodega', 'Producto', 'Produccion', 'Fecha Actual', 'Fecha Documento'],
                $sample->map(fn ($row) => [
                    $row->id,
                    mb_substr($row->bodega, 0, 20),
                    mb_substr($row->producto, 0, 25),
                    $row->production_number,
                    $row->fecha_actual,
                    $row->fecha_documento,
                ])->toArray()
            );

            if ($count > 20) {
                $this->line("... mostrando solo 20 de {$count} registros.");
            }
        }

        $this->newLine();

        return $count;
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
