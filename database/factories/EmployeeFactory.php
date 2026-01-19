<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'area_id' => Area::factory(),
            'employee_code' => 'EMP-'.fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'position' => fake()->randomElement([
                'Jefe de Departamento',
                'Asistente',
                'Coordinador',
                'Encargado',
                'Supervisor',
                'Técnico',
                null,
            ]),
            'phone' => fake()->numerify('2###-####'),
            'mobile' => fake()->numerify('7###-####'),
            'email' => fake()->unique()->safeEmail(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'active_at' => now(),
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'active_at' => null,
        ]);
    }
}
