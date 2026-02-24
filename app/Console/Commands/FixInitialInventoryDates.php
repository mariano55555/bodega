<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixInitialInventoryDates extends Command
{
    protected $signature = 'app:fix-initial-inventory-dates
                            {--dry-run : Mostrar registros afectados sin modificar nada}';

    protected $description = 'Mueve la fecha de los movimientos de inventario inicial (INIT) de 2026-01-01 a 2025-12-31 para que el reporte consolidado los muestre como existencia inicial';

    public function handle(): int
    {
        $query = InventoryMovement::where('movement_type', 'adjustment')
            ->where(function ($q) {
                $q->where('reference_number', 'like', '%INIT%')
                    ->orWhere('reference_number', 'like', 'INI-%');
            })
            ->where('movement_date', '2026-01-01')
            ->whereNull('deleted_at');

        $affected = $query->clone()
            ->select('warehouse_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(quantity_in) as total_quantity'))
            ->groupBy('warehouse_id')
            ->get();

        if ($affected->isEmpty()) {
            $this->info('No se encontraron registros de inventario inicial con fecha 2026-01-01.');

            return self::SUCCESS;
        }

        $this->table(
            ['Bodega ID', 'Registros', 'Cantidad Total'],
            $affected->map(fn ($row) => [
                $row->warehouse_id,
                $row->total,
                number_format((float) $row->total_quantity, 2),
            ])
        );

        $totalRecords = $affected->sum('total');
        $this->newLine();
        $this->info("Total: {$totalRecords} registros serán actualizados de 2026-01-01 → 2025-12-31");

        if ($this->option('dry-run')) {
            $this->warn('Modo dry-run: no se realizaron cambios.');

            return self::SUCCESS;
        }

        if (! $this->confirm('¿Desea continuar con la actualización?')) {
            $this->warn('Operación cancelada.');

            return self::SUCCESS;
        }

        $updated = $query->update(['movement_date' => '2025-12-31']);

        $this->info("✓ {$updated} registros actualizados exitosamente.");

        return self::SUCCESS;
    }
}
