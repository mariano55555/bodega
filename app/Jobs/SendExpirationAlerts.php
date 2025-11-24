<?php

namespace App\Jobs;

use App\Events\LotExpirationAlert;
use App\Models\ProductLot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendExpirationAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $expirationWarningDays = 30
    ) {
        $this->onQueue('alerts');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando revisión de alertas de vencimiento', [
            'warning_days' => $this->expirationWarningDays,
        ]);

        try {
            // Buscar lotes que vencen pronto
            $expiringSoonLots = ProductLot::active()
                ->available()
                ->expiringSoon($this->expirationWarningDays)
                ->with(['product'])
                ->get();

            foreach ($expiringSoonLots as $lot) {
                $daysUntilExpiration = $lot->daysUntilExpiration();

                if ($daysUntilExpiration !== null && $daysUntilExpiration >= 0) {
                    event(new LotExpirationAlert($lot, $daysUntilExpiration, 'expiring_soon'));

                    Log::info('Alerta de vencimiento enviada', [
                        'lot_id' => $lot->id,
                        'lot_number' => $lot->lot_number,
                        'product_id' => $lot->product_id,
                        'days_until_expiration' => $daysUntilExpiration,
                    ]);
                }
            }

            // Buscar lotes ya vencidos
            $expiredLots = ProductLot::active()
                ->available()
                ->expired()
                ->with(['product'])
                ->get();

            foreach ($expiredLots as $lot) {
                $daysUntilExpiration = $lot->daysUntilExpiration();

                event(new LotExpirationAlert($lot, $daysUntilExpiration ?? 0, 'expired'));

                // Marcar el lote como vencido
                $lot->updateExpirationStatus();

                Log::warning('Lote vencido detectado', [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'product_id' => $lot->product_id,
                    'expiration_date' => $lot->expiration_date?->format('Y-m-d'),
                    'quantity_remaining' => $lot->quantity_remaining,
                ]);
            }

            Log::info('Revisión de alertas de vencimiento completada', [
                'expiring_soon_count' => $expiringSoonLots->count(),
                'expired_count' => $expiredLots->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en revisión de alertas de vencimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
