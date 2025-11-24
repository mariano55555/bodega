<?php

namespace App\Jobs;

use App\Events\MovementCompleted;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\MovementService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInventoryMovement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public InventoryMovement $movement,
        public int $userId
    ) {
        $this->onQueue('inventory-movements');
    }

    /**
     * Execute the job.
     */
    public function handle(MovementService $movementService): void
    {
        try {
            Log::info('Procesando movimiento de inventario', [
                'movement_id' => $this->movement->id,
                'user_id' => $this->userId,
                'movement_type' => $this->movement->movement_type,
                'quantity' => $this->movement->quantity,
            ]);

            // Verificar que el movimiento esté listo para procesar
            if (! $this->movement->canBeCompleted()) {
                Log::warning('Movimiento no puede ser completado', [
                    'movement_id' => $this->movement->id,
                    'status' => $this->movement->status,
                    'requires_approval' => $this->movement->movementReason?->requires_approval,
                    'approved_at' => $this->movement->approved_at,
                ]);

                return;
            }

            DB::transaction(function () use ($movementService) {
                // Ejecutar el movimiento usando el servicio
                $result = $movementService->executeMovement($this->movement, $this->userId);

                if (! $result['success']) {
                    throw new Exception($result['message']);
                }

                // Marcar como completado
                $this->movement->complete($this->userId);

                // Disparar evento de completado
                $user = User::find($this->userId);
                event(new MovementCompleted(
                    $this->movement->fresh(),
                    $user,
                    $result['inventory_changes'] ?? []
                ));

                Log::info('Movimiento de inventario procesado exitosamente', [
                    'movement_id' => $this->movement->id,
                    'user_id' => $this->userId,
                    'inventory_changes' => $result['inventory_changes'] ?? [],
                ]);
            });

        } catch (Exception $e) {
            Log::error('Error procesando movimiento de inventario', [
                'movement_id' => $this->movement->id,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Marcar el movimiento como fallido si es el último intento
            if ($this->attempts() >= $this->tries) {
                $this->movement->update([
                    'status' => 'failed',
                    'notes' => 'Error en procesamiento: '.$e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Falló el procesamiento del movimiento de inventario', [
            'movement_id' => $this->movement->id,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Notificar al usuario que creó el movimiento
        // Aquí se podría enviar un email o notificación push
    }
}
