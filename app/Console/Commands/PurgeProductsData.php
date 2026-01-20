<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeProductsData extends Command
{
    protected $signature = 'products:purge
                            {--force : Ejecutar sin confirmación}
                            {--company= : ID de la empresa (opcional, si no se especifica elimina de todas)}';

    protected $description = 'Elimina todos los productos y datos relacionados (movimientos, traslados, despachos, ajustes, etc.) manteniendo datos maestros';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $force = $this->option('force');

        $this->warn('╔════════════════════════════════════════════════════════════════╗');
        $this->warn('║  ADVERTENCIA: Este comando eliminará PERMANENTEMENTE:          ║');
        $this->warn('╠════════════════════════════════════════════════════════════════╣');
        $this->warn('║  • Productos                                                   ║');
        $this->warn('║  • Inventario                                                  ║');
        $this->warn('║  • Movimientos de inventario                                   ║');
        $this->warn('║  • Traslados y detalles                                        ║');
        $this->warn('║  • Despachos y detalles                                        ║');
        $this->warn('║  • Ajustes de inventario                                       ║');
        $this->warn('║  • Compras y detalles                                          ║');
        $this->warn('║  • Donaciones y detalles                                       ║');
        $this->warn('║  • Alertas de inventario                                       ║');
        $this->warn('║  • Lotes de productos                                          ║');
        $this->warn('║  • Cierres de inventario y detalles                            ║');
        $this->warn('║  • Relaciones producto-proveedor                               ║');
        $this->warn('║  • Importaciones DTE                                           ║');
        $this->warn('╠════════════════════════════════════════════════════════════════╣');
        $this->warn('║  SE MANTENDRÁN: Categorías, Sucursales, Bodegas, Proveedores,  ║');
        $this->warn('║  Unidades de medida, Usuarios, Empleados, Donantes, etc.       ║');
        $this->warn('╚════════════════════════════════════════════════════════════════╝');

        if ($companyId) {
            $this->info("Empresa seleccionada: ID {$companyId}");
        } else {
            $this->error('¡Se eliminarán datos de TODAS las empresas!');
        }

        if (! $force && ! $this->confirm('¿Está seguro de que desea continuar? Esta acción NO se puede deshacer.')) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm('¿Confirma que tiene un respaldo de la base de datos?')) {
            $this->info('Operación cancelada. Por favor realice un respaldo antes de continuar.');

            return self::SUCCESS;
        }

        $this->info('Iniciando eliminación de datos...');

        try {
            // Desactivar verificación de claves foráneas temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Orden de eliminación respetando dependencias
            $tables = [
                'inventory_closure_details' => 'Detalles de cierres de inventario',
                'inventory_closures' => 'Cierres de inventario',
                'inventory_alerts' => 'Alertas de inventario',
                'inventory_movements' => 'Movimientos de inventario',
                'inventory_adjustments' => 'Ajustes de inventario',
                'inventory_transfer_details' => 'Detalles de traslados',
                'inventory_transfers' => 'Traslados',
                'dispatch_details' => 'Detalles de despachos',
                'dispatches' => 'Despachos',
                'purchase_details' => 'Detalles de compras',
                'purchases' => 'Compras',
                'donation_details' => 'Detalles de donaciones',
                'donations' => 'Donaciones',
                'product_lots' => 'Lotes de productos',
                'product_supplier' => 'Relaciones producto-proveedor',
                'dte_imports' => 'Importaciones DTE',
                'inventory' => 'Inventario',
                'products' => 'Productos',
            ];

            $bar = $this->output->createProgressBar(count($tables));
            $bar->start();

            foreach ($tables as $table => $description) {
                if ($companyId) {
                    $count = $this->deleteByCompany($table, (int) $companyId);
                } else {
                    $count = DB::table($table)->count();
                    // Usar DELETE en lugar de TRUNCATE para evitar commit implícito
                    DB::table($table)->delete();
                }

                $this->newLine();
                $this->line("  ✓ {$description}: {$count} registros eliminados");
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Reactivar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('╔════════════════════════════════════════════════════════════════╗');
            $this->info('║  ✓ Eliminación completada exitosamente                         ║');
            $this->info('╚════════════════════════════════════════════════════════════════╝');

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->error('Error durante la eliminación: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function deleteByCompany(string $table, int $companyId): int
    {
        // Tablas que tienen company_id directamente
        $directCompanyTables = [
            'products',
            'inventory',
            'inventory_alerts',
            'inventory_adjustments',
            'inventory_transfers',
            'dispatches',
            'purchases',
            'donations',
            'inventory_closures',
            'dte_imports',
        ];

        if (in_array($table, $directCompanyTables)) {
            $count = DB::table($table)->where('company_id', $companyId)->count();
            DB::table($table)->where('company_id', $companyId)->delete();

            return $count;
        }

        // Tablas de detalle - eliminar basándose en la tabla padre
        return match ($table) {
            'inventory_closure_details' => $this->deleteByParent(
                $table,
                'inventory_closure_id',
                'inventory_closures',
                $companyId
            ),
            'inventory_transfer_details' => $this->deleteByParent(
                $table,
                'inventory_transfer_id',
                'inventory_transfers',
                $companyId
            ),
            'dispatch_details' => $this->deleteByParent(
                $table,
                'dispatch_id',
                'dispatches',
                $companyId
            ),
            'purchase_details' => $this->deleteByParent(
                $table,
                'purchase_id',
                'purchases',
                $companyId
            ),
            'donation_details' => $this->deleteByParent(
                $table,
                'donation_id',
                'donations',
                $companyId
            ),
            'product_lots', 'product_supplier', 'inventory_movements' => $this->deleteByProduct(
                $table,
                $companyId
            ),
            default => 0,
        };
    }

    private function deleteByParent(string $table, string $foreignKey, string $parentTable, int $companyId): int
    {
        $parentIds = DB::table($parentTable)
            ->where('company_id', $companyId)
            ->pluck('id');

        $count = DB::table($table)->whereIn($foreignKey, $parentIds)->count();
        DB::table($table)->whereIn($foreignKey, $parentIds)->delete();

        return $count;
    }

    private function deleteByProduct(string $table, int $companyId): int
    {
        $productIds = DB::table('products')
            ->where('company_id', $companyId)
            ->pluck('id');

        $count = DB::table($table)->whereIn('product_id', $productIds)->count();
        DB::table($table)->whereIn('product_id', $productIds)->delete();

        return $count;
    }
}
