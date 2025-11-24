<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Almacén Central',
            'Almacén Principal',
            'Bodega A',
            'Bodega B',
            'Depósito Norte',
            'Depósito Sur',
        ]).' '.fake()->city();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'description' => fake()->optional()->sentence(),
            'company_id' => \App\Models\Company::factory(),
            'branch_id' => \App\Models\Branch::factory(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->optional()->latitude(-90, 90),
            'longitude' => fake()->optional()->longitude(-180, 180),
            'total_capacity' => fake()->randomFloat(2, 100, 10000),
            'capacity_unit' => fake()->randomElement(['m3', 'm2', 'pallets', 'containers']),
            'manager_id' => null,
            'is_active' => true,
            'active_at' => now(),
            'operating_hours' => [
                'monday' => ['open' => '08:00', 'close' => '18:00'],
                'tuesday' => ['open' => '08:00', 'close' => '18:00'],
                'wednesday' => ['open' => '08:00', 'close' => '18:00'],
                'thursday' => ['open' => '08:00', 'close' => '18:00'],
                'friday' => ['open' => '08:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '13:00'],
                'sunday' => ['open' => null, 'close' => null],
            ],
            'settings' => [],
        ];
    }

    /**
     * Indicate that the warehouse is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Set warehouse with specific capacity.
     */
    public function withCapacity(float $capacity, string $unit = 'm3'): static
    {
        return $this->state(fn (array $attributes) => [
            'total_capacity' => $capacity,
            'capacity_unit' => $unit,
        ]);
    }

    /**
     * Set warehouse with manager.
     */
    public function withManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'manager_id' => \App\Models\User::factory(),
        ]);
    }
}
