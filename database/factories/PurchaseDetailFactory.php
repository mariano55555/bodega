<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseDetail>
 */
class PurchaseDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 100);
        $unitCost = fake()->randomFloat(2, 10, 500);
        $discountPercentage = fake()->optional(0.3)->randomFloat(2, 5, 20) ?? 0;
        $taxPercentage = fake()->randomElement([0, 16]); // 0% or 16% IVA

        // Calculations will be handled by the model's boot method
        return [
            'purchase_id' => \App\Models\Purchase::factory(),
            'product_id' => \App\Models\Product::factory(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => 0, // Will be calculated by model
            'tax_percentage' => $taxPercentage,
            'tax_amount' => 0, // Will be calculated by model
            'subtotal' => 0, // Will be calculated by model
            'total' => 0, // Will be calculated by model
            'lot_number' => fake()->optional()->regexify('LOT-[0-9]{4}-[A-Z]{2}'),
            'expiration_date' => fake()->optional()->dateTimeBetween('+6 months', '+2 years'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the detail has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => 0,
            'discount_amount' => 0,
        ]);
    }

    /**
     * Indicate that the detail has no tax.
     */
    public function noTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_percentage' => 0,
            'tax_amount' => 0,
        ]);
    }

    /**
     * Indicate that the detail has a lot number and expiration date.
     */
    public function withLot(): static
    {
        return $this->state(fn (array $attributes) => [
            'lot_number' => fake()->regexify('LOT-[0-9]{4}-[A-Z]{2}'),
            'expiration_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
        ]);
    }

    /**
     * Indicate a specific quantity.
     */
    public function quantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Indicate a specific unit cost.
     */
    public function unitCost(float $cost): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_cost' => $cost,
        ]);
    }
}
