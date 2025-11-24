<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Distribuidora',
            'Importadora',
            'Comercial',
            'Corporación',
            'Empresa',
            'Grupo',
        ]).' '.fake()->company();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'legal_name' => fake()->optional()->company(),
            'company_id' => \App\Models\Company::factory(),
            'tax_id' => fake()->optional()->numerify('##-#######-#'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->optional()->safeEmail(),
            'contact_phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'payment_terms' => fake()->randomElement(['15 días', '30 días', '45 días', '60 días', '90 días']),
            'credit_limit' => fake()->optional()->randomFloat(2, 5000, 100000),
            'rating' => fake()->optional()->numberBetween(1, 5),
            'notes' => fake()->optional()->paragraph(),
            'is_active' => true,
            'active_at' => now(),
        ];
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Indicate that the supplier is preferred with higher credit limit.
     */
    public function preferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => fake()->randomFloat(2, 50000, 500000),
            'payment_terms' => '60 días',
            'rating' => 5,
        ]);
    }

    /**
     * Indicate that the supplier has a specific rating.
     */
    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }
}
