<?php

namespace App\Services;

use App\Events\MovementApproved;
use App\Events\MovementRequested;
use App\Jobs\UpdateInventoryLevels;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\ProductLot;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MovementService
{
    /**
     * Create a new movement service instance.
     */
    public function __construct(
        private LotRotationService $lotRotationService
    ) {
        //
    }

    /**
     * Request a new inventory movement.
     *
     * @param  array  $data  Movement data
     * @return array Result with success status and movement data
     */
    public function requestMovement(array $data): array
    {
        try {
            Log::info('Solicitando movimiento de inventario', $data);

            // Validate movement data
            $this->validateMovementData($data);

            // Generate movement number
            $data['movement_number'] = $this->generateMovementNumber($data['movement_type']);

            DB::beginTransaction();

            // Create the movement record
            $movement = InventoryMovement::create($data);

            // Perform business rule validations
            $validationResult = $this->validateBusinessRules($movement);
            if (! $validationResult['valid']) {
                throw new ValidationException($validationResult['message']);
            }

            // If the movement requires approval, set it to pending
            if ($this->requiresApproval($movement)) {
                $movement->update(['status' => 'pending']);
            } else {
                // Auto-approve simple movements
                $movement->update(['status' => 'approved']);
            }

            DB::commit();

            // Fire movement requested event
            event(new MovementRequested($movement, $data));

            Log::info('Movimiento de inventario solicitado exitosamente', [
                'movement_id' => $movement->id,
                'movement_number' => $movement->movement_number,
                'status' => $movement->status,
            ]);

            return [
                'success' => true,
                'movement' => $movement,
                'message' => 'Movimiento solicitado exitosamente',
                'requires_approval' => $this->requiresApproval($movement),
            ];

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error solicitando movimiento de inventario', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Approve a pending movement.
     *
     * @param  int  $movementId  Movement ID
     * @param  int  $userId  User ID who approves
     * @param  string|null  $notes  Approval notes
     * @return array Result with success status
     */
    public function approveMovement(int $movementId, int $userId, ?string $notes = null): array
    {
        try {
            $movement = InventoryMovement::findOrFail($movementId);
            $user = User::findOrFail($userId);

            if (! $movement->canBeApproved()) {
                return [
                    'success' => false,
                    'message' => 'El movimiento no puede ser aprobado en su estado actual',
                ];
            }

            $movement->approve($userId, $notes);

            // Fire movement approved event
            event(new MovementApproved($movement->fresh(), $user, $notes));

            Log::info('Movimiento de inventario aprobado', [
                'movement_id' => $movementId,
                'approved_by' => $userId,
                'notes' => $notes,
            ]);

            return [
                'success' => true,
                'movement' => $movement->fresh(),
                'message' => 'Movimiento aprobado exitosamente',
            ];

        } catch (Exception $e) {
            Log::error('Error aprobando movimiento de inventario', [
                'movement_id' => $movementId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute an approved movement.
     *
     * @param  InventoryMovement  $movement  Movement to execute
     * @param  int  $userId  User ID who executes
     * @return array Result with success status and inventory changes
     */
    public function executeMovement(InventoryMovement $movement, int $userId): array
    {
        try {
            Log::info('Ejecutando movimiento de inventario', [
                'movement_id' => $movement->id,
                'user_id' => $userId,
            ]);

            if (! $movement->canBeCompleted()) {
                return [
                    'success' => false,
                    'message' => 'El movimiento no puede ser ejecutado en su estado actual',
                ];
            }

            $inventoryChanges = [];

            DB::transaction(function () use ($movement, &$inventoryChanges) {
                // Handle lot selection for outbound movements
                if ($movement->movement_type === 'out' && ! $movement->product_lot_id) {
                    $selectedLots = $this->selectOptimalLots(
                        $movement->product_id,
                        $movement->quantity,
                        'FEFO' // Default to FEFO for outbound movements
                    );

                    if (empty($selectedLots)) {
                        throw new Exception('No hay suficiente stock disponible para este movimiento');
                    }

                    // For simplicity, use the first lot. In a real scenario, you might split the movement
                    $movement->product_lot_id = $selectedLots[0]['lot_id'];
                    $movement->save();
                }

                // Validate inventory availability for outbound movements
                if (in_array($movement->movement_type, ['out', 'transfer'])) {
                    if (! $this->validateInventoryAvailability($movement)) {
                        throw new Exception('Stock insuficiente para completar el movimiento');
                    }
                }

                // Update inventory levels asynchronously
                UpdateInventoryLevels::dispatch($movement);

                $inventoryChanges = $this->calculateInventoryChanges($movement);
            });

            Log::info('Movimiento de inventario ejecutado exitosamente', [
                'movement_id' => $movement->id,
                'inventory_changes' => $inventoryChanges,
            ]);

            return [
                'success' => true,
                'movement' => $movement,
                'inventory_changes' => $inventoryChanges,
                'message' => 'Movimiento ejecutado exitosamente',
            ];

        } catch (Exception $e) {
            Log::error('Error ejecutando movimiento de inventario', [
                'movement_id' => $movement->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Select optimal lots for a product movement using FIFO or FEFO.
     *
     * @param  int  $productId  Product ID
     * @param  float  $quantity  Required quantity
     * @param  string  $method  Selection method (FIFO or FEFO)
     * @return array Array of selected lots
     */
    public function selectOptimalLots(int $productId, float $quantity, string $method = 'FIFO'): array
    {
        return $this->lotRotationService->selectLots($productId, $quantity, $method);
    }

    /**
     * Generate a unique movement number.
     *
     * @param  string  $movementType  Movement type
     * @return string Generated movement number
     */
    private function generateMovementNumber(string $movementType): string
    {
        $prefix = match ($movementType) {
            'in' => 'ENT',
            'out' => 'SAL',
            'transfer' => 'TRF',
            'adjustment' => 'AJU',
            default => 'MOV',
        };

        $date = now()->format('Ymd');
        $sequence = InventoryMovement::whereDate('created_at', now())
            ->where('movement_type', $movementType)
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Validate movement data.
     *
     * @param  array  $data  Movement data
     *
     * @throws ValidationException
     */
    private function validateMovementData(array $data): void
    {
        $required = ['product_id', 'movement_type', 'quantity', 'movement_reason_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new ValidationException("El campo {$field} es requerido");
            }
        }

        if ($data['quantity'] <= 0) {
            throw new ValidationException('La cantidad debe ser mayor a cero');
        }

        // Validate movement reason exists
        if (! MovementReason::find($data['movement_reason_id'])) {
            throw new ValidationException('El motivo de movimiento especificado no es válido');
        }
    }

    /**
     * Validate business rules for the movement.
     *
     * @param  InventoryMovement  $movement  Movement to validate
     * @return array Validation result
     */
    private function validateBusinessRules(InventoryMovement $movement): array
    {
        try {
            // Check if product lot is expired for outbound movements
            if ($movement->product_lot_id && in_array($movement->movement_type, ['out', 'transfer'])) {
                $lot = ProductLot::find($movement->product_lot_id);
                if ($lot && $lot->isExpired()) {
                    return [
                        'valid' => false,
                        'message' => 'No se puede usar un lote vencido para movimientos de salida',
                    ];
                }
            }

            // Validate warehouse relationships for transfers
            if ($movement->movement_type === 'transfer') {
                if ($movement->from_warehouse_id === $movement->to_warehouse_id) {
                    return [
                        'valid' => false,
                        'message' => 'Los almacenes de origen y destino no pueden ser el mismo',
                    ];
                }
            }

            return ['valid' => true];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if movement requires approval.
     *
     * @param  InventoryMovement  $movement  Movement to check
     * @return bool True if requires approval
     */
    private function requiresApproval(InventoryMovement $movement): bool
    {
        $reason = $movement->movementReason;
        if (! $reason) {
            return false;
        }

        // Check if reason requires approval
        if ($reason->requires_approval) {
            // Check if value threshold is met
            $totalValue = $movement->quantity * ($movement->unit_cost ?? 0);

            return $reason->requiresApprovalForValue($totalValue);
        }

        return false;
    }

    /**
     * Validate inventory availability for outbound movements.
     *
     * @param  InventoryMovement  $movement  Movement to validate
     * @return bool True if inventory is available
     */
    private function validateInventoryAvailability(InventoryMovement $movement): bool
    {
        if ($movement->product_lot_id) {
            $lot = ProductLot::find($movement->product_lot_id);

            return $lot && $lot->quantity_remaining >= $movement->quantity;
        }

        // Check total available inventory for the product
        $totalAvailable = ProductLot::forProduct($movement->product_id)
            ->available()
            ->sum('quantity_remaining');

        return $totalAvailable >= $movement->quantity;
    }

    /**
     * Calculate inventory changes for a movement.
     *
     * @param  InventoryMovement  $movement  Movement to calculate
     * @return array Inventory changes data
     */
    private function calculateInventoryChanges(InventoryMovement $movement): array
    {
        $changes = [
            'product_id' => $movement->product_id,
            'warehouse_id' => $movement->warehouse_id,
            'movement_type' => $movement->movement_type,
            'quantity_change' => 0,
        ];

        switch ($movement->movement_type) {
            case 'in':
                $changes['quantity_change'] = $movement->quantity;
                break;
            case 'out':
                $changes['quantity_change'] = -$movement->quantity;
                break;
            case 'transfer':
                $changes['from_warehouse_id'] = $movement->from_warehouse_id;
                $changes['to_warehouse_id'] = $movement->to_warehouse_id;
                $changes['quantity_change'] = 0; // Net zero for transfers
                break;
            case 'adjustment':
                $changes['quantity_change'] = $movement->quantity; // Can be positive or negative
                break;
        }

        return $changes;
    }
}
