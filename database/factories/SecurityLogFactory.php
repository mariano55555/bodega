<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecurityLog>
 */
class SecurityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventTypes = ['login', 'logout', 'failed_login', 'password_change', 'permission_denied'];
        $severities = ['info', 'warning', 'error', 'critical'];

        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'event_type' => fake()->randomElement($eventTypes),
            'severity' => fake()->randomElement($severities),
            'description' => fake()->sentence(),
            'metadata' => ['action' => fake()->word()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'url' => fake()->url(),
            'status_code' => fake()->randomElement([200, 201, 403, 404]),
            'country' => fake()->countryCode(),
            'city' => fake()->city(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'login',
            'severity' => 'info',
            'status_code' => 200,
        ]);
    }

    public function failedLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'failed_login',
            'severity' => 'warning',
            'status_code' => 401,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }
}
