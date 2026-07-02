<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDocumentDateTyposCommand extends Command
{
    protected $signature = 'inventory:fix-document-date-typos
                            {--dry-run : Mostrar cambios sin aplicar}
                            {--force : Aplicar sin pedir confirmacion}
                            {--year-threshold=100 : Corregir fechas cuyo año sea menor a este valor}';

    protected $description = 'Corrige fechas de documento con año erroneo (ej: 0026 -> 2026), resincroniza los movimientos de inventario y recalcula saldos';

    /**
     * Source tables that carry a user-entered document_date feeding movement_date.
     *
     * @var array<int, array{table: string, number: string, label: string}>
     */
    private array $sources = [
        ['table' => 'purchases', 'number' => 'purchase_number', 'label' => 'Compras'],
        ['table' => 'dispatches', 'number' => 'dispatch_number', 'label' => 'Despachos'],
        ['table' => 'inventory_transfers', 'number' => 'transfer_number', 'label' => 'Traslados'],
        ['table' => 'donations', 'number' => 'donation_number', 'label' => 'Donaciones'],
        ['table' => 'internal_productions', 'number' => 'production_number', 'label' => 'Producciones internas'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $threshold = (int) $this->option('year-threshold');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se haran cambios en la base de datos.');
            $this->newLine();
        }

        $this->info("Buscando fechas de documento con año menor a {$threshold}...");
        $this->newLine();

        $totalDocs = 0;

        foreach ($this->sources as $source) {
            $totalDocs += $this->showAffectedSource($source, $threshold);
        }

        $badMovements = InventoryMovement::whereRaw('YEAR(movement_date) < ?', [$threshold])->count();
        $this->info("Movimientos de inventario con año menor a {$threshold}: {$badMovements}");
        $this->newLine();

        if ($totalDocs === 0 && $badMovements === 0) {
            $this->info('No hay fechas que corregir. Todo esta correcto.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Documentos a corregir: {$totalDocs}. Movimientos a resincronizar: {$badMovements}.");
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Se corregiran {$totalDocs} documentos y {$badMovements} movimientos, y se recalcularan saldos. Continuar?")) {
            $this->info('Operacion cancelada.');

            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // Capture affected product/warehouse combos BEFORE fixing dates (they still carry the bad year).
            $combos = InventoryMovement::query()
                ->whereRaw('YEAR(movement_date) < ?', [$threshold])
                ->selectRaw('DISTINCT product_id, warehouse_id')
                ->get();

            // 1. Fix document_date on every source table (0026 -> 2026 by adding 2000 years).
            foreach ($this->sources as $source) {
                $updated = DB::update(
                    "UPDATE {$source['table']}
                     SET document_date = DATE_ADD(document_date, INTERVAL 2000 YEAR)
                     WHERE document_date IS NOT NULL AND YEAR(document_date) < ?",
                    [$threshold]
                );

                if ($updated > 0) {
                    $this->info("{$source['label']} corregidos: {$updated}");
                }
            }

            // 2. Resync movement_date from the corrected document_date for the affected movements only.
            $this->syncMovementDates('purchases', 'purchase_id', $threshold);
            $this->syncMovementDates('dispatches', 'dispatch_id', $threshold);
            $this->syncMovementDates('inventory_transfers', 'transfer_id', $threshold);
            $this->syncMovementDates('donations', 'donation_id', $threshold);
            $this->syncProductionMovementDates($threshold);

            // 3. Recalculate running balances for the affected product/warehouse combinations.
            $this->newLine();
            $this->info('Recalculando saldos...');

            $bar = $this->output->createProgressBar($combos->count());
            $bar->start();

            foreach ($combos as $combo) {
                $this->recalculateBalances($combo->product_id, $combo->warehouse_id);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info("Proceso completado. Se recalcularon saldos de {$combos->count()} combinaciones producto/bodega.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * @param  array{table: string, number: string, label: string}  $source
     */
    private function showAffectedSource(array $source, int $threshold): int
    {
        $count = DB::table($source['table'])
            ->whereNull('deleted_at')
            ->whereNotNull('document_date')
            ->whereRaw('YEAR(document_date) < ?', [$threshold])
            ->count();

        $this->info("{$source['label']} con fecha incorrecta: {$count}");

        if ($count > 0) {
            $rows = DB::table($source['table'])
                ->whereNull('deleted_at')
                ->whereNotNull('document_date')
                ->whereRaw('YEAR(document_date) < ?', [$threshold])
                ->selectRaw("{$source['number']} as numero, document_date as fecha_actual, DATE_ADD(document_date, INTERVAL 2000 YEAR) as fecha_corregida")
                ->limit(20)
                ->get();

            $this->table(
                ['Documento', 'Fecha Actual', 'Fecha Corregida'],
                $rows->map(fn ($row) => [$row->numero, $row->fecha_actual, $row->fecha_corregida])->toArray()
            );

            if ($count > 20) {
                $this->line("... mostrando solo 20 de {$count} registros.");
            }
        }

        $this->newLine();

        return $count;
    }

    private function syncMovementDates(string $table, string $foreignKey, int $threshold): void
    {
        $updated = DB::update(
            "UPDATE inventory_movements im
             JOIN {$table} src ON src.id = im.{$foreignKey}
             SET im.movement_date = src.document_date
             WHERE src.document_date IS NOT NULL AND YEAR(im.movement_date) < ?",
            [$threshold]
        );

        if ($updated > 0) {
            $this->info("Movimientos resincronizados ({$table}): {$updated}");
        }
    }

    private function syncProductionMovementDates(int $threshold): void
    {
        $updated = DB::update(
            'UPDATE inventory_movements im
             JOIN internal_productions ip ON ip.production_number = im.document_number
             SET im.movement_date = ip.document_date
             WHERE im.movement_type = ? AND ip.document_date IS NOT NULL AND YEAR(im.movement_date) < ?',
            ['production', $threshold]
        );

        if ($updated > 0) {
            $this->info("Movimientos resincronizados (internal_productions): {$updated}");
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
