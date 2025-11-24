<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DispatchDetail>
 */
class DispatchDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(4, 1, 1000);
        $subtotal = $quantity * $unitPrice;

        return [
            'dispatch_id' => 1,
            'product_id' => 1,
            'product_lot_id' => null,
            'quantity' => $quantity,
            'quantity_dispatched' => 0,
            'quantity_delivered' => 0,
            'unit_of_measure_id' => 1,
            'unit_price' => $unitPrice,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'notes' => fake()->optional()->sentence(),
            'is_reserved' => false,
        ];
    }
}
