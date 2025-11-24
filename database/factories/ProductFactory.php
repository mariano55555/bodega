<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->numerify('SKU-#####'),
            'description' => fake()->optional()->paragraph(),
            'unit_of_measure' => fake()->randomElement(['unidad', 'caja', 'paquete', 'kg', 'litro']),
            'cost' => fake()->randomFloat(2, 10, 1000),
            'price' => fake()->randomFloat(2, 15, 1500),
            'barcode' => fake()->optional()->ean13(),
            'track_inventory' => fake()->boolean(80),
            'is_active' => true,
            'active_at' => now(),
            'valuation_method' => fake()->randomElement(['fifo', 'lifo', 'average']),
            'minimum_stock' => fake()->randomFloat(2, 5, 50),
            'maximum_stock' => fake()->randomFloat(2, 100, 500),
        ];
    }
}
