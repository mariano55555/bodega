<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserWarehouseAccess>
 */
class UserWarehouseAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'is_active' => true,
            'granted_at' => now(),
            'granted_by' => \App\Models\User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => \App\Models\User::factory(),
        ]);
    }
}
