<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\MovementReason;
use Illuminate\Console\Command;

class FixMissingMovementReasonsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-missing-reasons
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix inventory movements that have no movement_reason_id assigned';

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

        $this->info('Buscando movimientos sin motivo asignado...');
        $this->newLine();

        // Get movement reasons
        $initialStock = MovementReason::where('code', 'INITIAL_STOCK')->first();
        $adjPos = MovementReason::where('code', 'ADJ_POS')->first();
        $adjNeg = MovementReason::where('code', 'ADJ_NEG')->first();

        if (! $initialStock || ! $adjPos || ! $adjNeg) {
            $this->error('No se encontraron los motivos de movimiento necesarios (INITIAL_STOCK, ADJ_POS, ADJ_NEG).');

            return Command::FAILURE;
        }

        $this->info("INITIAL_STOCK: ID {$initialStock->id} - {$initialStock->name}");
        $this->info("ADJ_POS: ID {$adjPos->id} - {$adjPos->name}");
        $this->info("ADJ_NEG: ID {$adjNeg->id} - {$adjNeg->name}");
        $this->newLine();

        // Count affected movements by type
        $initialCount = InventoryMovement::whereNull('movement_reason_id')
            ->where('notes', 'like', '%Inventario inicial%')
            ->count();

        $adjPosCount = InventoryMovement::whereNull('movement_reason_id')
            ->where('notes', 'not like', '%Inventario inicial%')
            ->where('quantity_in', '>', 0)
            ->count();

        $adjNegCount = InventoryMovement::whereNull('movement_reason_id')
            ->where('notes', 'not like', '%Inventario inicial%')
            ->where('quantity_out', '>', 0)
            ->count();

        $otherCount = InventoryMovement::whereNull('movement_reason_id')
            ->where(function ($q) {
                $q->whereNull('notes')
                    ->orWhere('notes', 'not like', '%Inventario inicial%');
            })
            ->where('quantity_in', 0)
            ->where('quantity_out', 0)
            ->count();

        $totalCount = $initialCount + $adjPosCount + $adjNegCount + $otherCount;

        if ($totalCount === 0) {
            $this->info('No se encontraron movimientos sin motivo asignado.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Tipo', 'Cantidad', 'Se asignará'],
            [
                ['Inventario Inicial', $initialCount, 'INITIAL_STOCK'],
                ['Ajustes Positivos', $adjPosCount, 'ADJ_POS'],
                ['Ajustes Negativos', $adjNegCount, 'ADJ_NEG'],
                ['Otros', $otherCount, 'ADJ_POS (entrada) / ADJ_NEG (salida)'],
            ]
        );
        $this->newLine();
        $this->warn("Total de movimientos a actualizar: {$totalCount}");
        $this->newLine();

        if ($dryRun) {
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        if (! $this->confirm('¿Deseas continuar con la actualización?')) {
            $this->info('Operación cancelada.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Actualizando movimientos...');

        // Fix initial inventory movements
        $updatedInitial = InventoryMovement::whereNull('movement_reason_id')
            ->where('notes', 'like', '%Inventario inicial%')
            ->update(['movement_reason_id' => $initialStock->id]);
        $this->line("  - Inventario Inicial (INITIAL_STOCK): {$updatedInitial}");

        // Fix positive adjustments
        $updatedPos = InventoryMovement::whereNull('movement_reason_id')
            ->where('quantity_in', '>', 0)
            ->update(['movement_reason_id' => $adjPos->id]);
        $this->line("  - Ajustes Positivos (ADJ_POS): {$updatedPos}");

        // Fix negative adjustments
        $updatedNeg = InventoryMovement::whereNull('movement_reason_id')
            ->where('quantity_out', '>', 0)
            ->update(['movement_reason_id' => $adjNeg->id]);
        $this->line("  - Ajustes Negativos (ADJ_NEG): {$updatedNeg}");

        // Check remaining
        $remaining = InventoryMovement::whereNull('movement_reason_id')->count();

        $this->newLine();
        $total = $updatedInitial + $updatedPos + $updatedNeg;
        $this->info("Total actualizados: {$total}");

        if ($remaining > 0) {
            $this->warn("Movimientos restantes sin motivo: {$remaining}");
        } else {
            $this->info('Todos los movimientos tienen motivo asignado.');
        }

        $this->newLine();

        return Command::SUCCESS;
    }
}
