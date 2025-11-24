<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispatch>
 */
class DispatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'warehouse_id' => 1,
            'customer_id' => fake()->optional()->numberBetween(1, 10),
            'dispatch_type' => fake()->randomElement(['venta', 'interno', 'externo', 'donacion']),
            'destination_unit' => fake()->optional()->company(),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->phoneNumber(),
            'recipient_email' => fake()->optional()->safeEmail(),
            'delivery_address' => fake()->address(),
            'document_type' => fake()->randomElement(['factura', 'remision', 'guia', 'otro']),
            'document_number' => fake()->optional()->numerify('DOC-####'),
            'document_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'shipping_cost' => fake()->randomFloat(2, 0, 100),
            'notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['borrador', 'pendiente', 'aprobado', 'despachado', 'entregado']),
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'is_active' => true,
            'active_at' => now(),
        ];
    }
}
