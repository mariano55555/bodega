<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Sucursal Principal',
            'Sucursal Norte',
            'Sucursal Sur',
            'Sucursal Centro',
            'Sucursal Oriente',
            'Sucursal Occidente',
        ]).' '.fake()->city();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => fake()->optional()->sentence(),
            'company_id' => \App\Models\Company::factory(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'manager_id' => null,
            'settings' => [],
            'is_active' => true,
            'active_at' => now(),
        ];
    }

    /**
     * Indicate that the branch has a manager.
     */
    public function withManager(\App\Models\User $manager): static
    {
        return $this->state(fn (array $attributes) => [
            'manager_id' => $manager->id,
        ]);
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Set the branch type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
