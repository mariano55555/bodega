<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFloatingPointQuantities extends Command
{
    protected $signature = 'fix:floating-point-quantities {--dry-run : Solo mostrar lo que se corregiría sin hacer cambios}';

    protected $description = 'Corrige cantidades con residuos de punto flotante (ej: 199.99997 → 200, 0.00003 → 0)';

    /**
     * Threshold to detect floating point residue.
     * If the fractional part is within this distance of a round number, it's considered a residue.
     */
    private const THRESHOLD = 0.001;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('=== MODO DRY-RUN: No se harán cambios ===');
        }

        $this->info('Buscando cantidades con residuos de punto flotante...');
        $this->newLine();

        $totalFixed = 0;

        $totalFixed += $this->fixTable('inventory_movements', [
            'quantity', 'quantity_in', 'quantity_out', 'balance_quantity',
            'previous_quantity', 'new_quantity', 'total_cost',
        ], $dryRun);

        $totalFixed += $this->fixTable('inventory_transfer_details', [
            'quantity',
        ], $dryRun);

        $totalFixed += $this->fixTable('inventory', [
            'quantity', 'reserved_quantity', 'available_quantity', 'total_value',
        ], $dryRun);

        $totalFixed += $this->fixTable('dispatch_details', [
            'quantity', 'quantity_dispatched', 'quantity_delivered',
        ], $dryRun);

        $totalFixed += $this->fixTable('purchase_details', [
            'quantity', 'quantity_received',
        ], $dryRun);

        $this->newLine();
        if ($dryRun) {
            $this->info("Total de valores que se corregirían: {$totalFixed}");
        } else {
            $this->info("Total de valores corregidos: {$totalFixed}");
        }

        return self::SUCCESS;
    }

    private function fixTable(string $table, array $columns, bool $dryRun): int
    {
        $this->info("--- Tabla: {$table} ---");
        $fixed = 0;

        // Check if table exists
        if (! \Schema::hasTable($table)) {
            $this->warn("  Tabla '{$table}' no existe, saltando...");

            return 0;
        }

        foreach ($columns as $column) {
            if (! \Schema::hasColumn($table, $column)) {
                continue;
            }

            // Find rows where the value has a suspicious fractional residue
            // A value like 199.99997 → round to 200, diff = 0.00003 < threshold
            // A value like 0.00003 → round to 0, diff = 0.00003 < threshold
            // A value like 10.50000 → round to 11, diff = 0.5 > threshold (leave alone)
            $rows = DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', 0)
                ->whereRaw("ABS({$column} - ROUND({$column}, 0)) > 0 AND ABS({$column} - ROUND({$column}, 0)) < ?", [self::THRESHOLD])
                ->select('id', $column)
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            foreach ($rows as $row) {
                $original = (float) $row->$column;
                $rounded = round($original);

                $this->line("  [{$table}] ID={$row->id} {$column}: {$original} → {$rounded}");

                if (! $dryRun) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$column => $rounded]);
                }

                $fixed++;
            }
        }

        if ($fixed === 0) {
            $this->line('  Sin residuos encontrados.');
        }

        return $fixed;
    }
}
