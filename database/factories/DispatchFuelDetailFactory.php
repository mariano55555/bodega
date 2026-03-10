<?php

namespace Database\Factories;

use App\Models\Dispatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DispatchFuelDetail>
 */
class DispatchFuelDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispatch_id' => Dispatch::factory(),
            'vehicle_class' => fake()->randomElement(['Camioneta', 'Tractor', 'Bus', 'Camión', 'Motocicleta']),
            'vehicle_brand' => fake()->randomElement(['Toyota', 'Nissan', 'John Deere', 'Mitsubishi', 'Hyundai']),
            'vehicle_model' => fake()->optional()->word(),
            'vehicle_plate' => fake()->bothify('P-###-###'),
            'odometer_reading' => fake()->optional()->randomFloat(2, 1000, 500000),
            'horometer_reading' => fake()->optional()->randomFloat(2, 100, 50000),
            'place_to_visit' => fake()->city(),
            'mission_description' => fake()->sentence(),
            'kilometers_to_travel' => fake()->optional()->randomFloat(2, 5, 500),
            'is_active' => true,
            'active_at' => now(),
        ];
    }
}
