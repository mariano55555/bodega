<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donor>
 */
class DonorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $donorTypes = ['individual', 'organization', 'government', 'ngo', 'international'];

        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company().' '.fake()->companySuffix(),
            'donor_type' => fake()->randomElement($donorTypes),
            'tax_id' => fake()->numerify('##-#######'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->email(),
            'contact_phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'rating' => fake()->numberBetween(1, 5),
            'notes' => fake()->optional()->paragraph(),
            'is_active' => true,
            'active_at' => now(),
        ];
    }
}
