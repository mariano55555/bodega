<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryAdjustment>
 */
class InventoryAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adjustmentTypes = ['positive', 'negative', 'damage', 'expiry', 'loss', 'correction', 'return', 'other'];
        $adjustmentType = fake()->randomElement($adjustmentTypes);
        $isNegative = in_array($adjustmentType, ['negative', 'damage', 'expiry', 'loss']);

        $quantity = fake()->randomFloat(4, 1, 1000);
        if ($isNegative) {
            $quantity = -$quantity;
        }

        return [
            'company_id' => \App\Models\Company::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'product_id' => \App\Models\Product::factory(),
            'adjustment_type' => $adjustmentType,
            'quantity' => $quantity,
            'unit_cost' => fake()->randomFloat(4, 0.01, 1000),
            'reason' => fake()->sentence(),
            'justification' => fake()->optional()->paragraph(),
            'corrective_actions' => fake()->optional()->paragraph(),
            'reference_document' => fake()->optional()->word(),
            'reference_number' => fake()->optional()->bothify('REF-####-???'),
            'batch_number' => fake()->optional()->bothify('LOTE-####-???'),
            'expiry_date' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'notes' => fake()->optional()->text(200),
            'cost_center' => fake()->optional()->bothify('CC-###'),
            'project_code' => fake()->optional()->bothify('PROY-####'),
            'department' => fake()->optional()->word(),
            'status' => 'borrador',
            'is_active' => true,
            'active_at' => now(),
        ];
    }

    /**
     * Indicate that the adjustment is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'borrador',
            'submitted_at' => null,
            'submitted_by' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'processed_at' => null,
            'processed_by' => null,
        ]);
    }

    /**
     * Indicate that the adjustment is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendiente',
            'submitted_at' => now()->subHours(2),
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'processed_at' => null,
            'processed_by' => null,
        ])->afterCreating(function ($adjustment) {
            if (! $adjustment->submitted_by) {
                $adjustment->submitted_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
                $adjustment->save();
            }
        });
    }

    /**
     * Indicate that the adjustment is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprobado',
            'submitted_at' => now()->subHours(4),
            'approved_at' => now()->subHours(2),
            'rejected_at' => null,
            'rejected_by' => null,
            'processed_at' => null,
            'processed_by' => null,
        ])->afterCreating(function ($adjustment) {
            if (! $adjustment->submitted_by) {
                $adjustment->submitted_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            if (! $adjustment->approved_by) {
                $adjustment->approved_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            $adjustment->save();
        });
    }

    /**
     * Indicate that the adjustment is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rechazado',
            'submitted_at' => now()->subHours(4),
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => now()->subHours(2),
            'rejection_reason' => fake()->sentence(),
            'processed_at' => null,
            'processed_by' => null,
        ])->afterCreating(function ($adjustment) {
            if (! $adjustment->submitted_by) {
                $adjustment->submitted_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            if (! $adjustment->rejected_by) {
                $adjustment->rejected_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            $adjustment->save();
        });
    }

    /**
     * Indicate that the adjustment is processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'procesado',
            'submitted_at' => now()->subHours(6),
            'approved_at' => now()->subHours(4),
            'rejected_at' => null,
            'rejected_by' => null,
            'processed_at' => now()->subHours(2),
        ])->afterCreating(function ($adjustment) {
            if (! $adjustment->submitted_by) {
                $adjustment->submitted_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            if (! $adjustment->approved_by) {
                $adjustment->approved_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            if (! $adjustment->processed_by) {
                $adjustment->processed_by = \App\Models\User::inRandomOrder()->first()?->id
                    ?? \App\Models\User::factory()->create(['company_id' => $adjustment->company_id])->id;
            }
            if (! $adjustment->inventory_movement_id) {
                // Create a related inventory movement for this adjustment
                $adjustment->inventory_movement_id = \App\Models\InventoryMovement::create([
                    'movement_type' => 'adjustment',
                    'product_id' => $adjustment->product_id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'movement_reason_id' => \App\Models\MovementReason::where('movement_type', 'adjustment')->inRandomOrder()->first()?->id,
                    'quantity' => $adjustment->quantity,
                    'unit_cost' => $adjustment->unit_cost,
                    'total_cost' => $adjustment->quantity * $adjustment->unit_cost,
                    'reference_number' => 'AJU-'.now()->format('Ymd').'-'.str_pad($adjustment->id, 4, '0', STR_PAD_LEFT),
                    'status' => 'completed',
                    'is_confirmed' => true,
                    'confirmed_at' => now(),
                    'confirmed_by' => $adjustment->approved_by,
                    'completed_at' => $adjustment->processed_at,
                    'completed_by' => $adjustment->processed_by,
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => $adjustment->processed_by,
                ])->id;
            }
            $adjustment->save();
        });
    }

    /**
     * Indicate that the adjustment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
        ]);
    }

    /**
     * Indicate that the adjustment is a positive adjustment.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => 'positive',
            'quantity' => abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 1000)),
        ]);
    }

    /**
     * Indicate that the adjustment is a negative adjustment.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => 'negative',
            'quantity' => -abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 1000)),
        ]);
    }

    /**
     * Indicate that the adjustment is for damaged products.
     */
    public function damage(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => 'damage',
            'quantity' => -abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 1000)),
            'reason' => 'Producto dañado durante almacenamiento',
        ]);
    }

    /**
     * Indicate that the adjustment is for expired products.
     */
    public function expiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => 'expiry',
            'quantity' => -abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 1000)),
            'reason' => 'Producto vencido',
            'expiry_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
