<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryClosureDetail>
 */
class InventoryClosureDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openingQty = fake()->randomFloat(4, 10, 1000);
        $openingCost = fake()->randomFloat(2, 5, 500);
        $openingValue = $openingQty * $openingCost;

        $qtyIn = fake()->randomFloat(4, 0, 200);
        $qtyOut = fake()->randomFloat(4, 0, 150);
        $movementCount = fake()->numberBetween(0, 50);

        $calculatedQty = $openingQty + $qtyIn - $qtyOut;
        $calculatedCost = $openingCost; // Simplified for factory
        $calculatedValue = $calculatedQty * $calculatedCost;

        return [
            'inventory_closure_id' => \App\Models\InventoryClosure::factory(),
            'product_id' => \App\Models\Product::factory(),
            'opening_quantity' => $openingQty,
            'opening_unit_cost' => $openingCost,
            'opening_total_value' => $openingValue,
            'quantity_in' => $qtyIn,
            'quantity_out' => $qtyOut,
            'movement_count' => $movementCount,
            'calculated_closing_quantity' => $calculatedQty,
            'calculated_closing_unit_cost' => $calculatedCost,
            'calculated_closing_value' => $calculatedValue,
            'physical_count_quantity' => null,
            'physical_count_unit_cost' => null,
            'physical_count_value' => null,
            'physical_count_date' => null,
            'counted_by' => null,
            'discrepancy_quantity' => 0,
            'discrepancy_value' => 0,
            'has_discrepancy' => false,
            'discrepancy_notes' => null,
            'adjusted_closing_quantity' => $calculatedQty,
            'adjusted_closing_unit_cost' => $calculatedCost,
            'adjusted_closing_value' => $calculatedValue,
            'is_adjusted' => false,
            'adjustment_notes' => null,
            'is_active' => true,
            'below_minimum' => fake()->boolean(20),
            'above_maximum' => fake()->boolean(10),
            'needs_reorder' => fake()->boolean(25),
            'notes' => fake()->optional()->sentence(),
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the detail has a physical count.
     */
    public function withPhysicalCount(): static
    {
        return $this->state(function (array $attributes) {
            $physicalQty = $attributes['calculated_closing_quantity'] + fake()->randomFloat(2, -10, 10);
            $physicalCost = $attributes['calculated_closing_unit_cost'];
            $physicalValue = $physicalQty * $physicalCost;

            $discrepancyQty = $physicalQty - $attributes['calculated_closing_quantity'];
            $discrepancyValue = $physicalValue - $attributes['calculated_closing_value'];

            return [
                'physical_count_quantity' => $physicalQty,
                'physical_count_unit_cost' => $physicalCost,
                'physical_count_value' => $physicalValue,
                'physical_count_date' => now(),
                'discrepancy_quantity' => $discrepancyQty,
                'discrepancy_value' => $discrepancyValue,
                'has_discrepancy' => abs($discrepancyQty) > 0.0001,
                'adjusted_closing_quantity' => $physicalQty,
                'adjusted_closing_unit_cost' => $physicalCost,
                'adjusted_closing_value' => $physicalValue,
                'is_adjusted' => abs($discrepancyQty) > 0.0001,
            ];
        })->afterCreating(function ($detail) {
            if ($detail->counted_by === null) {
                $detail->counted_by = \App\Models\User::factory()->create([
                    'company_id' => $detail->closure->company_id,
                ])->id;
                $detail->save();
            }
        });
    }

    /**
     * Indicate that the detail has a discrepancy.
     */
    public function withDiscrepancy(): static
    {
        return $this->state(function (array $attributes) {
            $physicalQty = $attributes['calculated_closing_quantity'] - fake()->randomFloat(2, 5, 50);
            $physicalCost = $attributes['calculated_closing_unit_cost'];
            $physicalValue = $physicalQty * $physicalCost;

            $discrepancyQty = $physicalQty - $attributes['calculated_closing_quantity'];
            $discrepancyValue = $physicalValue - $attributes['calculated_closing_value'];

            return [
                'physical_count_quantity' => $physicalQty,
                'physical_count_unit_cost' => $physicalCost,
                'physical_count_value' => $physicalValue,
                'physical_count_date' => now(),
                'discrepancy_quantity' => $discrepancyQty,
                'discrepancy_value' => $discrepancyValue,
                'has_discrepancy' => true,
                'discrepancy_notes' => fake()->sentence(),
                'adjusted_closing_quantity' => $physicalQty,
                'adjusted_closing_unit_cost' => $physicalCost,
                'adjusted_closing_value' => $physicalValue,
                'is_adjusted' => true,
                'adjustment_notes' => fake()->sentence(),
            ];
        })->afterCreating(function ($detail) {
            if ($detail->counted_by === null) {
                $detail->counted_by = \App\Models\User::factory()->create([
                    'company_id' => $detail->closure->company_id,
                ])->id;
                $detail->save();
            }
        });
    }

    /**
     * Indicate that the detail is below minimum stock.
     */
    public function belowMinimum(): static
    {
        return $this->state(fn (array $attributes) => [
            'below_minimum' => true,
            'needs_reorder' => true,
        ]);
    }

    /**
     * Indicate that the detail is above maximum stock.
     */
    public function aboveMaximum(): static
    {
        return $this->state(fn (array $attributes) => [
            'above_maximum' => true,
        ]);
    }

    /**
     * Indicate that the detail needs reordering.
     */
    public function needsReorder(): static
    {
        return $this->state(fn (array $attributes) => [
            'needs_reorder' => true,
            'below_minimum' => true,
        ]);
    }
}
