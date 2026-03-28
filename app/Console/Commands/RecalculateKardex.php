<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\KardexService;
use Illuminate\Console\Command;

class RecalculateKardex extends Command
{
    /**
     * @var string
     */
    protected $signature = 'kardex:recalculate
        {--company= : ID de la empresa (opcional)}
        {--warehouse= : ID de la bodega (opcional)}
        {--product= : ID del producto (opcional)}
        {--dry-run : Solo mostrar lo que se arreglaría, sin hacer cambios}';

    /**
     * @var string
     */
    protected $description = 'Recalcular los saldos del kardex (balance_quantity, previous_quantity, new_quantity) para corregir inconsistencias';

    public function handle(KardexService $kardexService): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $warehouseId = $this->option('warehouse') ? (int) $this->option('warehouse') : null;
        $productId = $this->option('product') ? (int) $this->option('product') : null;
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se harán cambios en la base de datos.');
            $this->newLine();
        }

        // If specific product and warehouse, recalculate just that one
        if ($productId && $warehouseId) {
            return $this->recalculateSingle($kardexService, $warehouseId, $productId, $dryRun);
        }

        // Otherwise, recalculate all combinations
        return $this->recalculateAll($kardexService, $companyId, $warehouseId, $dryRun);
    }

    private function recalculateSingle(KardexService $kardexService, int $warehouseId, int $productId, bool $dryRun): int
    {
        $warehouse = Warehouse::find($warehouseId);
        $product = Product::find($productId);

        $this->info("Recalculando kardex: {$product?->name} en {$warehouse?->name}...");

        if ($dryRun) {
            $issues = $this->detectIssues($warehouseId, $productId);
            if ($issues > 0) {
                $this->warn("  Se encontraron {$issues} movimientos con saldos incorrectos.");
            } else {
                $this->info('  Kardex correcto, sin problemas.');
            }

            return self::SUCCESS;
        }

        $fixed = $kardexService->recalculateKardex($warehouseId, $productId);

        if ($fixed > 0) {
            $this->warn("  Corregidos: {$fixed} movimientos.");
        } else {
            $this->info('  Kardex correcto, sin cambios necesarios.');
        }

        return self::SUCCESS;
    }

    private function recalculateAll(KardexService $kardexService, ?int $companyId, ?int $warehouseId, bool $dryRun): int
    {
        $query = InventoryMovement::query()
            ->whereNotNull('balance_quantity')
            ->select('warehouse_id', 'product_id')
            ->distinct();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $combinations = $query->get();
        $totalCombinations = $combinations->count();

        $this->info("Procesando {$totalCombinations} combinaciones producto/bodega...");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalCombinations);
        $bar->start();

        $totalFixed = 0;
        $problemProducts = [];

        foreach ($combinations as $combo) {
            if ($dryRun) {
                $issues = $this->detectIssues($combo->warehouse_id, $combo->product_id);
                if ($issues > 0) {
                    $problemProducts[] = [
                        'warehouse_id' => $combo->warehouse_id,
                        'product_id' => $combo->product_id,
                        'issues' => $issues,
                    ];
                    $totalFixed += $issues;
                }
            } else {
                $fixed = $kardexService->recalculateKardex($combo->warehouse_id, $combo->product_id);
                if ($fixed > 0) {
                    $problemProducts[] = [
                        'warehouse_id' => $combo->warehouse_id,
                        'product_id' => $combo->product_id,
                        'fixed' => $fixed,
                    ];
                    $totalFixed += $fixed;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (empty($problemProducts)) {
            $this->info('Todos los kardex están correctos. No se necesitaron cambios.');

            return self::SUCCESS;
        }

        // Show details table
        $headers = $dryRun
            ? ['Bodega ID', 'Bodega', 'Producto ID', 'Producto', 'Movimientos con problemas']
            : ['Bodega ID', 'Bodega', 'Producto ID', 'Producto', 'Movimientos corregidos'];

        $rows = [];
        foreach ($problemProducts as $item) {
            $warehouse = Warehouse::find($item['warehouse_id']);
            $product = Product::find($item['product_id']);
            $rows[] = [
                $item['warehouse_id'],
                $warehouse?->name ?? 'N/A',
                $item['product_id'],
                $product?->name ?? 'N/A',
                $item[$dryRun ? 'issues' : 'fixed'],
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        $label = $dryRun ? 'con problemas' : 'corregidos';
        $this->info("Total combinaciones procesadas: {$totalCombinations}");
        $this->info("Total combinaciones {$label}: ".count($problemProducts));
        $this->info("Total movimientos {$label}: {$totalFixed}");

        return self::SUCCESS;
    }

    /**
     * Detect how many movements have incorrect balances without changing them.
     */
    private function detectIssues(int $warehouseId, int $productId): int
    {
        $movements = InventoryMovement::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNotNull('balance_quantity')
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        $issues = 0;

        foreach ($movements as $movement) {
            $quantityIn = (float) $movement->quantity_in;
            $quantityOut = (float) $movement->quantity_out;
            $expectedBalance = $runningBalance + $quantityIn - $quantityOut;

            if (round((float) $movement->balance_quantity, 5) !== round($expectedBalance, 5)) {
                $issues++;
            }

            $runningBalance = $expectedBalance;
        }

        return $issues;
    }
}
