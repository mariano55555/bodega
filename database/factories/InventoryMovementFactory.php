<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // New standardized movement types
        $movementTypes = ['in', 'out', 'transfer', 'adjustment'];
        $statuses = ['pending', 'approved', 'completed', 'cancelled', 'rejected'];
        $documentTypes = ['invoice', 'receipt', 'order', 'transfer', 'adjustment'];

        $quantity = $this->faker->randomFloat(4, 1, 1000);
        $unitCost = $this->faker->randomFloat(4, 0.01, 500);
        $movementType = $this->faker->randomElement($movementTypes);

        return [
            // Basic movement information
            'movement_number' => $this->generateMovementNumber($movementType),
            'movement_type' => $movementType,
            'product_id' => \App\Models\Product::factory(),
            'product_lot_id' => $this->faker->optional(0.7)->randomElement([
                \App\Models\ProductLot::factory(),
                fn () => \App\Models\ProductLot::inRandomOrder()->first()?->id,
            ]),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'movement_reason_id' => \App\Models\MovementReason::factory(),

            // Quantity and cost information
            'quantity' => $quantity,
            'previous_quantity' => $this->faker->optional(0.5)->randomFloat(4, 0, 5000),
            'new_quantity' => $this->faker->optional(0.5)->randomFloat(4, 0, 5000),
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,

            // Location information
            'from_warehouse_id' => $this->faker->optional(0.3)->randomElement([
                \App\Models\Warehouse::factory(),
                fn () => \App\Models\Warehouse::inRandomOrder()->first()?->id,
            ]),
            'to_warehouse_id' => $this->faker->optional(0.3)->randomElement([
                \App\Models\Warehouse::factory(),
                fn () => \App\Models\Warehouse::inRandomOrder()->first()?->id,
            ]),
            'from_storage_location_id' => $this->faker->optional(0.4)->randomElement([
                \App\Models\StorageLocation::factory(),
                fn () => \App\Models\StorageLocation::inRandomOrder()->first()?->id,
            ]),
            'to_storage_location_id' => $this->faker->optional(0.4)->randomElement([
                \App\Models\StorageLocation::factory(),
                fn () => \App\Models\StorageLocation::inRandomOrder()->first()?->id,
            ]),

            // Documentation
            'reference_number' => $this->faker->unique()->numerify('REF-####'),
            'document_type' => $this->faker->randomElement($documentTypes),
            'document_number' => $this->faker->numerify('DOC-######'),
            'notes' => $this->faker->optional(0.4)->sentence(),

            // Lot tracking
            'lot_number' => $this->faker->optional(0.6)->bothify('LOT-##??-####'),
            'batch_number' => $this->faker->optional(0.4)->bothify('BAT-####'),
            'expiration_date' => $this->faker->optional(0.5)->dateTimeBetween('now', '+2 years'),
            'location' => $this->faker->optional(0.3)->bothify('A##-B##'),

            // Status and workflow
            'status' => $this->faker->randomElement($statuses),
            'scheduled_at' => $this->faker->optional(0.2)->dateTimeBetween('now', '+1 week'),

            // Approval workflow
            'is_confirmed' => $this->faker->boolean(80),
            'confirmed_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'confirmed_by' => $this->faker->optional(0.8)->randomElement([
                \App\Models\User::factory(),
                fn () => \App\Models\User::inRandomOrder()->first()?->id,
            ]),
            'approved_by' => $this->faker->optional(0.3)->randomElement([
                \App\Models\User::factory(),
                fn () => \App\Models\User::inRandomOrder()->first()?->id,
            ]),
            'approved_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'approval_notes' => $this->faker->optional(0.2)->sentence(),

            // Completion tracking
            'completed_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 week', 'now'),
            'completed_by' => $this->faker->optional(0.6)->randomElement([
                \App\Models\User::factory(),
                fn () => \App\Models\User::inRandomOrder()->first()?->id,
            ]),

            // Quality control
            'requires_quality_check' => $this->faker->boolean(20),
            'quality_approved' => $this->faker->optional(0.2)->boolean(),
            'quality_checked_by' => $this->faker->optional(0.1)->randomElement([
                \App\Models\User::factory(),
                fn () => \App\Models\User::inRandomOrder()->first()?->id,
            ]),
            'quality_checked_at' => $this->faker->optional(0.1)->dateTimeBetween('-1 week', 'now'),
            'quality_notes' => $this->faker->optional(0.1)->sentence(),

            // Metadata
            'metadata' => $this->faker->optional(0.3)->randomElement([
                ['source' => 'manual', 'priority' => 'normal'],
                ['source' => 'automated', 'batch_process' => true],
                null,
            ]),
            'movement_data' => $this->faker->optional(0.2)->randomElement([
                ['temperature' => '20°C', 'humidity' => '65%'],
                ['special_handling' => true, 'fragile' => true],
                null,
            ]),

            // Standard audit fields
            'is_active' => true,
            'active_at' => now(),
            'created_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Generate a movement number based on type.
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
        $sequence = $this->faker->numberBetween(1, 9999);

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Create an inbound movement (incoming inventory).
     */
    public function inbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'in',
            'quantity' => abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 1000)),
            'document_type' => 'invoice',
            'movement_reason_id' => \App\Models\MovementReason::factory()->inbound(),
        ]);
    }

    /**
     * Create an outbound movement (outgoing inventory).
     */
    public function outbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'out',
            'quantity' => abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100)),
            'document_type' => 'invoice',
            'movement_reason_id' => \App\Models\MovementReason::factory()->outbound(),
        ]);
    }

    /**
     * Create an adjustment movement.
     */
    public function adjustment(bool $positive = true): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'adjustment',
            'quantity' => $positive ?
                abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 50)) :
                -abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 50)),
            'document_type' => 'adjustment',
            'notes' => $positive ? 'Ajuste positivo - stock encontrado' : 'Ajuste negativo - discrepancia de stock',
            'movement_reason_id' => \App\Models\MovementReason::factory()->adjustment(),
        ]);
    }

    /**
     * Create a transfer movement.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'transfer',
            'quantity' => abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100)),
            'from_warehouse_id' => \App\Models\Warehouse::factory(),
            'to_warehouse_id' => \App\Models\Warehouse::factory(),
            'document_type' => 'transfer',
            'movement_reason_id' => \App\Models\MovementReason::factory()->transfer(),
        ]);
    }

    /**
     * Create a pending movement (not approved yet).
     */
    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'is_confirmed' => false,
            'confirmed_at' => null,
            'confirmed_by' => null,
            'approved_at' => null,
            'approved_by' => null,
            'completed_at' => null,
            'completed_by' => null,
        ]);
    }

    /**
     * Create an approved movement.
     */
    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'approved_by' => \App\Models\User::factory(),
            'approval_notes' => $this->faker->optional(0.5)->sentence(),
        ]);
    }

    /**
     * Create a completed movement.
     */
    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'completed_by' => \App\Models\User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
            'approved_by' => \App\Models\User::factory(),
        ]);
    }

    /**
     * Create a movement requiring quality check.
     */
    public function requiresQualityCheck(): static
    {
        return $this->state([
            'requires_quality_check' => true,
            'quality_approved' => null,
            'quality_checked_by' => null,
            'quality_checked_at' => null,
        ]);
    }

    /**
     * Create a movement with specific product lot.
     */
    public function withProductLot(int $productLotId): static
    {
        return $this->state([
            'product_lot_id' => $productLotId,
        ]);
    }

    /**
     * Create a movement with specific lot tracking.
     */
    public function withLot(?string $lotNumber = null, ?\Carbon\Carbon $expirationDate = null): static
    {
        return $this->state([
            'lot_number' => $lotNumber ?? $this->faker->bothify('LOT-##??-####'),
            'batch_number' => $this->faker->bothify('BAT-####'),
            'expiration_date' => $expirationDate ?? $this->faker->dateTimeBetween('+1 month', '+2 years'),
        ]);
    }

    /**
     * Create a movement for specific warehouse transfer.
     */
    public function forTransfer(int $fromWarehouseId, int $toWarehouseId): static
    {
        return $this->state([
            'movement_type' => 'transfer',
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'transfer_id' => \App\Models\InventoryTransfer::factory(),
            'movement_reason_id' => \App\Models\MovementReason::factory()->transfer(),
        ]);
    }

    /**
     * Create a high-value movement requiring approval.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->randomFloat(4, 100, 1000),
            'unit_cost' => $this->faker->randomFloat(4, 100, 1000),
            'total_cost' => ($attributes['quantity'] ?? 500) * ($attributes['unit_cost'] ?? 500),
            'movement_reason_id' => \App\Models\MovementReason::factory()->requiresApproval(),
            'requires_quality_check' => true,
        ]);
    }

    /**
     * Create a movement with storage locations.
     */
    public function withStorageLocations(?int $fromLocationId = null, ?int $toLocationId = null): static
    {
        return $this->state([
            'from_storage_location_id' => $fromLocationId,
            'to_storage_location_id' => $toLocationId,
        ]);
    }

    /**
     * Create a scheduled movement.
     */
    public function scheduled(?\Carbon\Carbon $scheduledAt = null): static
    {
        return $this->state([
            'scheduled_at' => $scheduledAt ?? $this->faker->dateTimeBetween('now', '+1 week'),
            'status' => 'pending',
        ]);
    }

    /**
     * Create a purchase movement (specific type of inbound).
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'purchase',
            'quantity' => abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 10, 1000)),
            'document_type' => 'invoice',
            'supplier_id' => \App\Models\Supplier::factory(),
            'notes' => 'Compra de mercadería',
        ]);
    }

    /**
     * Create a sale movement (specific type of outbound).
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'sale',
            'quantity' => -abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100)),
            'document_type' => 'invoice',
            'customer_id' => \App\Models\Customer::factory(),
            'notes' => 'Venta de producto',
        ]);
    }

    /**
     * Create a transfer out movement.
     */
    public function transferOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'transfer_out',
            'quantity' => -abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100)),
            'document_type' => 'transfer',
            'to_warehouse_id' => \App\Models\Warehouse::factory(),
            'notes' => 'Transferencia de salida',
        ]);
    }

    /**
     * Create a transfer in movement.
     */
    public function transferIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'transfer_in',
            'quantity' => abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100)),
            'document_type' => 'transfer',
            'from_warehouse_id' => \App\Models\Warehouse::factory(),
            'notes' => 'Transferencia de entrada',
        ]);
    }

    /**
     * Create a damage movement.
     */
    public function damage(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'damage',
            'quantity' => -abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 50)),
            'document_type' => 'damage_report',
            'unit_cost' => 0, // No cost recovery for damaged goods
            'notes' => 'Mercadería dañada - pérdida',
        ]);
    }

    /**
     * Create an expiry movement.
     */
    public function expiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'expiry',
            'quantity' => -abs($attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100)),
            'document_type' => 'disposal',
            'unit_cost' => 0, // No cost recovery for expired goods
            'expiration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => 'Producto vencido - disposición',
        ]);
    }

    /**
     * Create a movement with specific metadata for testing.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state([
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a movement for Spanish localization testing.
     */
    public function spanishContext(): static
    {
        $spanishNotes = [
            'Movimiento de inventario - recepción de mercadería',
            'Salida de producto para entrega a cliente',
            'Ajuste de inventario por conteo físico',
            'Transferencia entre almacenes',
            'Producto dañado durante manipulación',
            'Disposición de producto vencido',
        ];

        return $this->state([
            'notes' => $this->faker->randomElement($spanishNotes),
            'metadata' => [
                'language' => 'es',
                'region' => 'SV', // El Salvador
                'currency' => 'USD',
                'timezone' => 'America/El_Salvador',
            ],
        ]);
    }

    /**
     * Create a movement that will fail validation (for testing error handling).
     */
    public function invalid(): static
    {
        return $this->state([
            'movement_type' => 'invalid_type',
            'quantity' => 0, // Invalid quantity
            'unit_cost' => -10, // Invalid negative cost
            'status' => 'invalid_status',
        ]);
    }

    /**
     * Create a movement for FIFO testing.
     */
    public function fifoScenario(): static
    {
        return $this->state([
            'movement_type' => 'sale',
            'quantity' => -$this->faker->randomFloat(4, 5, 25),
            'document_type' => 'invoice',
            'notes' => 'Venta - debe usar lotes FIFO',
            'metadata' => [
                'rotation_strategy' => 'FIFO',
                'auto_lot_selection' => true,
            ],
        ]);
    }

    /**
     * Create a movement for FEFO testing.
     */
    public function fefoScenario(): static
    {
        return $this->state([
            'movement_type' => 'sale',
            'quantity' => -$this->faker->randomFloat(4, 5, 25),
            'document_type' => 'invoice',
            'notes' => 'Venta - debe usar lotes FEFO',
            'metadata' => [
                'rotation_strategy' => 'FEFO',
                'auto_lot_selection' => true,
                'priority_expiration' => true,
            ],
        ]);
    }
}
