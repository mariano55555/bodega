<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\MovementReason;
use Illuminate\Console\Command;

class FixDispatchMovementReasonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-dispatch-reasons
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix dispatch inventory movements that have incorrect movement reason (SALE_CREDIT instead of DISPATCH)';

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

        $this->info('Buscando movimientos de despacho con código de motivo incorrecto...');
        $this->newLine();

        // Get movement reasons
        $saleCredit = MovementReason::where('code', 'SALE_CREDIT')->first();
        $dispatch = MovementReason::where('code', 'DISPATCH')->first();

        if (! $saleCredit) {
            $this->error('No se encontró el motivo de movimiento SALE_CREDIT.');

            return Command::FAILURE;
        }

        if (! $dispatch) {
            $this->error('No se encontró el motivo de movimiento DISPATCH.');

            return Command::FAILURE;
        }

        $this->info("SALE_CREDIT (incorrecto): ID {$saleCredit->id} - {$saleCredit->name}");
        $this->info("DISPATCH (correcto): ID {$dispatch->id} - {$dispatch->name}");
        $this->newLine();

        // Find affected movements
        $affectedMovements = InventoryMovement::where('movement_reason_id', $saleCredit->id)
            ->whereNotNull('dispatch_id')
            ->get();

        $count = $affectedMovements->count();

        if ($count === 0) {
            $this->info('No se encontraron movimientos de despacho con código incorrecto.');

            return Command::SUCCESS;
        }

        $this->warn("Se encontraron {$count} movimientos de despacho con código incorrecto.");
        $this->newLine();

        // Show sample of affected movements
        $sample = $affectedMovements->take(10);
        $this->info('Muestra de movimientos afectados:');
        $this->table(
            ['ID', 'Dispatch ID', 'Product ID', 'Warehouse ID', 'Document Number', 'Movement Date'],
            $sample->map(fn ($m) => [
                $m->id,
                $m->dispatch_id,
                $m->product_id,
                $m->warehouse_id,
                $m->document_number,
                $m->movement_date?->format('Y-m-d'),
            ])->toArray()
        );

        if ($count > 10) {
            $remaining = $count - 10;
            $this->line("... y {$remaining} más.");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn("Se actualizarían {$count} movimientos de SALE_CREDIT a DISPATCH.");
            $this->newLine();
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (! $this->confirm("¿Deseas actualizar {$count} movimientos de SALE_CREDIT a DISPATCH?")) {
            $this->info('Operación cancelada.');

            return Command::SUCCESS;
        }

        // Perform the update
        $updated = InventoryMovement::where('movement_reason_id', $saleCredit->id)
            ->whereNotNull('dispatch_id')
            ->update(['movement_reason_id' => $dispatch->id]);

        $this->newLine();
        $this->info("✓ Se actualizaron {$updated} movimientos correctamente.");
        $this->newLine();

        return Command::SUCCESS;
    }
}
