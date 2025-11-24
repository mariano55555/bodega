<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryClosure>
 */
class InventoryClosureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2023, 2025);
        $month = fake()->numberBetween(1, 12);
        $closureNumber = sprintf('CLS-%04d%02d-%04d', $year, $month, fake()->unique()->numberBetween(1, 9999));

        $periodStart = date('Y-m-01', strtotime("$year-$month-01"));
        $periodEnd = date('Y-m-t', strtotime("$year-$month-01"));
        $closureDate = date('Y-m-d H:i:s', strtotime($periodEnd.' +'.rand(1, 5).' days'));

        return [
            'company_id' => \App\Models\Company::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'closure_number' => $closureNumber,
            'slug' => \Illuminate\Support\Str::slug($closureNumber),
            'year' => $year,
            'month' => $month,
            'closure_date' => $closureDate,
            'period_start_date' => $periodStart,
            'period_end_date' => $periodEnd,
            'status' => 'en_proceso',
            'total_products' => 0,
            'total_movements' => 0,
            'total_value' => 0,
            'total_quantity' => 0,
            'products_with_discrepancies' => 0,
            'total_discrepancy_value' => 0,
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'closed_at' => null,
            'closed_by' => null,
            'reopened_at' => null,
            'reopened_by' => null,
            'reopening_reason' => null,
            'notes' => fake()->optional()->sentence(),
            'observations' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the closure is in process.
     */
    public function inProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'en_proceso',
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    /**
     * Indicate that the closure is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'approved_at' => now()->subHours(2),
        ])->afterCreating(function ($closure) {
            if (! $closure->approved_by) {
                $closure->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $closure->company_id,
                ])->id;
                $closure->save();
            }
        });
    }

    /**
     * Indicate that the closure is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cerrado',
            'is_approved' => true,
            'approved_at' => now()->subDays(2),
            'closed_at' => now()->subHours(1),
        ])->afterCreating(function ($closure) {
            if (! $closure->approved_by) {
                $closure->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $closure->company_id,
                ])->id;
            }
            if (! $closure->closed_by) {
                $closure->closed_by = \App\Models\User::factory()->create([
                    'company_id' => $closure->company_id,
                ])->id;
            }
            $closure->save();
        });
    }

    /**
     * Indicate that the closure is reopened.
     */
    public function reopened(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reabierto',
            'is_approved' => true,
            'approved_at' => now()->subDays(5),
            'closed_at' => now()->subDays(3),
            'reopened_at' => now()->subHours(1),
            'reopening_reason' => fake()->sentence(),
        ])->afterCreating(function ($closure) {
            if (! $closure->approved_by) {
                $closure->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $closure->company_id,
                ])->id;
            }
            if (! $closure->closed_by) {
                $closure->closed_by = \App\Models\User::factory()->create([
                    'company_id' => $closure->company_id,
                ])->id;
            }
            if (! $closure->reopened_by) {
                $closure->reopened_by = \App\Models\User::factory()->create([
                    'company_id' => $closure->company_id,
                ])->id;
            }
            $closure->save();
        });
    }

    /**
     * Indicate that the closure is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
        ]);
    }

    /**
     * Indicate that the closure has discrepancies.
     */
    public function withDiscrepancies(): static
    {
        return $this->state(fn (array $attributes) => [
            'products_with_discrepancies' => fake()->numberBetween(1, 10),
            'total_discrepancy_value' => fake()->randomFloat(2, -1000, 1000),
        ]);
    }

    /**
     * Indicate that the closure has details.
     */
    public function withDetails(int $count = 5): static
    {
        return $this->has(
            \App\Models\InventoryClosureDetail::factory()->count($count),
            'details'
        );
    }

    /**
     * Set a specific year and month.
     */
    public function forPeriod(int $year, int $month): static
    {
        $closureNumber = sprintf('CLS-%04d%02d-%04d', $year, $month, fake()->unique()->numberBetween(1, 9999));
        $periodStart = date('Y-m-01', strtotime("$year-$month-01"));
        $periodEnd = date('Y-m-t', strtotime("$year-$month-01"));
        $closureDate = date('Y-m-d H:i:s', strtotime($periodEnd.' +'.rand(1, 5).' days'));

        return $this->state(fn (array $attributes) => [
            'year' => $year,
            'month' => $month,
            'closure_number' => $closureNumber,
            'slug' => \Illuminate\Support\Str::slug($closureNumber),
            'period_start_date' => $periodStart,
            'period_end_date' => $periodEnd,
            'closure_date' => $closureDate,
        ]);
    }
}
