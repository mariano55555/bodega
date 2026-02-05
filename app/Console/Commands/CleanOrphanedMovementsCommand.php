<?php

namespace App\Console\Commands;

use App\Models\Dispatch;
use App\Models\InventoryMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOrphanedMovementsCommand extends Command
{
    protected $signature = 'inventory:clean-orphaned-movements
                            {--dry-run : Mostrar los movimientos que se eliminarían sin eliminarlos}
                            {--force : Ejecutar sin confirmación}';

    protected $description = 'Elimina (soft delete) movimientos de inventario huérfanos de despachos eliminados';

    public function handle(): int
    {
        $this->info('Buscando movimientos de inventario huérfanos...');
        $this->newLine();

        $orphanedMovements = collect();

        // 1. Movimientos con dispatch_id que apunta a un despacho eliminado
        $this->info('1. Buscando movimientos con dispatch_id de despachos eliminados...');
        $withDeletedDispatch = InventoryMovement::whereNotNull('dispatch_id')
            ->whereHas('dispatch', fn ($q) => $q->onlyTrashed())
            ->get();

        if ($withDeletedDispatch->isNotEmpty()) {
            $this->table(
                ['ID', 'Fecha', 'Producto ID', 'Cantidad', 'Despacho ID', 'Notas'],
                $withDeletedDispatch->map(fn ($m) => [
                    $m->id,
                    $m->movement_date?->format('Y-m-d'),
                    $m->product_id,
                    $m->quantity,
                    $m->dispatch_id,
                    \Str::limit($m->notes, 40),
                ])
            );
            $orphanedMovements = $orphanedMovements->merge($withDeletedDispatch);
        } else {
            $this->info('   No se encontraron movimientos con dispatch_id de despachos eliminados.');
        }

        $this->newLine();

        // 2. Movimientos con notas de "Despacho Rápido" pero sin dispatch_id (despacho fue eliminado completamente)
        $this->info('2. Buscando movimientos de Despacho Rápido sin dispatch_id...');
        $quickDispatchOrphans = InventoryMovement::whereNull('dispatch_id')
            ->where('notes', 'like', '%Despacho Rápido%')
            ->get();

        if ($quickDispatchOrphans->isNotEmpty()) {
            // Verificar cuáles realmente son huérfanos (su despacho ya no existe)
            $realOrphans = $quickDispatchOrphans->filter(function ($movement) {
                // Extraer el número de despacho de las notas
                if (preg_match('/Despacho Rápido (BOD-\d+-D-\d+)/', $movement->notes, $matches)) {
                    $dispatchNumber = $matches[1];
                    // Verificar si el despacho existe (incluyendo eliminados)
                    $exists = Dispatch::withTrashed()
                        ->where('dispatch_number', $dispatchNumber)
                        ->exists();

                    return ! $exists; // Es huérfano si el despacho NO existe
                }

                return false;
            });

            if ($realOrphans->isNotEmpty()) {
                $this->table(
                    ['ID', 'Fecha', 'Producto ID', 'Cantidad', 'Notas'],
                    $realOrphans->map(fn ($m) => [
                        $m->id,
                        $m->movement_date?->format('Y-m-d'),
                        $m->product_id,
                        $m->quantity,
                        \Str::limit($m->notes, 50),
                    ])
                );
                $orphanedMovements = $orphanedMovements->merge($realOrphans);
            } else {
                $this->info('   No se encontraron movimientos huérfanos de Despacho Rápido.');
            }
        } else {
            $this->info('   No se encontraron movimientos de Despacho Rápido sin dispatch_id.');
        }

        $this->newLine();

        // Resumen
        $totalOrphaned = $orphanedMovements->count();
        $this->info("Total de movimientos huérfanos encontrados: {$totalOrphaned}");

        if ($totalOrphaned === 0) {
            $this->info('No hay movimientos huérfanos para eliminar.');

            return self::SUCCESS;
        }

        // Modo dry-run
        if ($this->option('dry-run')) {
            $this->warn('Modo dry-run: No se eliminaron movimientos.');
            $this->info('Ejecute sin --dry-run para eliminar los movimientos.');

            return self::SUCCESS;
        }

        // Confirmación
        if (! $this->option('force')) {
            if (! $this->confirm("¿Desea eliminar (soft delete) {$totalOrphaned} movimientos huérfanos?")) {
                $this->info('Operación cancelada.');

                return self::SUCCESS;
            }
        }

        // Eliminar movimientos
        $this->info('Eliminando movimientos huérfanos...');
        $deleted = 0;

        DB::beginTransaction();
        try {
            foreach ($orphanedMovements as $movement) {
                $movement->delete();
                $deleted++;
                $this->line("   Eliminado ID: {$movement->id}");
            }
            DB::commit();

            $this->newLine();
            $this->info("Se eliminaron {$deleted} movimientos huérfanos exitosamente.");

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al eliminar movimientos: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
