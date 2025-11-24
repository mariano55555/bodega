<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\AlertService;
use Illuminate\Console\Command;

class CheckInventoryAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:check
                            {--company= : Check alerts for a specific company ID}
                            {--type= : Check specific alert type (low_stock, out_of_stock, expiring, expired)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and create inventory alerts for low stock, out of stock, expiring and expired products';

    /**
     * Execute the console command.
     */
    public function handle(AlertService $alertService): int
    {
        $this->info('🔍 Iniciando verificación de alertas de inventario...');

        $companyId = $this->option('company');
        $type = $this->option('type');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
            if ($companies->isEmpty()) {
                $this->error("Compañía con ID {$companyId} no encontrada.");

                return self::FAILURE;
            }
        } else {
            $companies = Company::all();
        }

        $totalStats = [
            'low_stock_created' => 0,
            'out_of_stock_created' => 0,
            'expiring_created' => 0,
            'expired_created' => 0,
            'auto_resolved' => 0,
        ];

        foreach ($companies as $company) {
            $this->line("Procesando: {$company->name}...");

            if ($type) {
                $stats = $this->checkSpecificType($alertService, $company->id, $type);
            } else {
                $stats = $alertService->runAllChecks($company->id);
            }

            foreach ($stats as $key => $value) {
                $totalStats[$key] = ($totalStats[$key] ?? 0) + $value;
            }

            $this->displayCompanyStats($company->name, $stats);
        }

        $this->newLine();
        $this->info('✅ Verificación completada!');
        $this->displayTotalStats($totalStats);

        return self::SUCCESS;
    }

    /**
     * Check a specific alert type
     */
    protected function checkSpecificType(AlertService $alertService, int $companyId, string $type): array
    {
        return match ($type) {
            'low_stock' => ['low_stock_created' => $alertService->checkLowStockAlerts($companyId)],
            'out_of_stock' => ['out_of_stock_created' => $alertService->checkOutOfStockAlerts($companyId)],
            'expiring' => ['expiring_created' => $alertService->checkExpiringProductsAlerts($companyId)],
            'expired' => ['expired_created' => $alertService->checkExpiredProductsAlerts($companyId)],
            default => $alertService->runAllChecks($companyId),
        };
    }

    /**
     * Display stats for a company
     */
    protected function displayCompanyStats(string $companyName, array $stats): void
    {
        if (array_sum($stats) === 0) {
            $this->line("  ℹ️  No se crearon nuevas alertas para {$companyName}");

            return;
        }

        foreach ($stats as $key => $value) {
            if ($value > 0) {
                $label = match ($key) {
                    'low_stock_created' => 'Stock bajo',
                    'out_of_stock_created' => 'Sin stock',
                    'expiring_created' => 'Próximos a vencer',
                    'expired_created' => 'Vencidos',
                    'auto_resolved' => 'Auto-resueltas',
                    default => $key,
                };

                $this->line("  ✓ {$label}: {$value}");
            }
        }
    }

    /**
     * Display total stats
     */
    protected function displayTotalStats(array $stats): void
    {
        $this->table(
            ['Tipo de Alerta', 'Cantidad'],
            [
                ['Stock Bajo', $stats['low_stock_created']],
                ['Sin Stock', $stats['out_of_stock_created']],
                ['Próximos a Vencer', $stats['expiring_created']],
                ['Vencidos', $stats['expired_created']],
                ['Auto-Resueltas', $stats['auto_resolved']],
                ['TOTAL', array_sum($stats)],
            ]
        );
    }
}
