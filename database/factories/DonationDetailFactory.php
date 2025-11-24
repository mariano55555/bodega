<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DonationDetail>
 */
class DonationDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 100);
        $estimatedUnitValue = fake()->randomFloat(2, 5, 500);

        // Calculations will be handled by the model's boot method
        return [
            'donation_id' => \App\Models\Donation::factory(),
            'product_id' => \App\Models\Product::factory(),
            'quantity' => $quantity,
            'estimated_unit_value' => $estimatedUnitValue,
            'estimated_total_value' => 0, // Will be calculated by model
            'condition' => fake()->randomElement(['nuevo', 'usado', 'reacondicionado']),
            'condition_notes' => fake()->optional()->sentence(),
            'lot_number' => fake()->optional()->regexify('LOT-[0-9]{4}-[A-Z]{2}'),
            'expiration_date' => fake()->optional()->dateTimeBetween('+6 months', '+2 years'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the product is new.
     */
    public function newCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'nuevo',
            'condition_notes' => null,
        ]);
    }

    /**
     * Indicate that the product is used.
     */
    public function usedCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'usado',
            'condition_notes' => fake()->randomElement([
                'En buen estado general',
                'Señales leves de uso',
                'Funcional, con desgaste visible',
            ]),
        ]);
    }

    /**
     * Indicate that the product is refurbished.
     */
    public function refurbishedCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'reacondicionado',
            'condition_notes' => fake()->randomElement([
                'Reacondicionado por el donante',
                'Restaurado a condiciones óptimas',
                'Revisado y probado completamente',
            ]),
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
     * Indicate a specific unit value.
     */
    public function unitValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_unit_value' => $value,
        ]);
    }
}
