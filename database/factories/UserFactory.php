<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'company_id' => \App\Models\Company::factory(),
            'branch_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a super admin user.
     */
    public function superAdmin(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('super-admin');
        });
    }

    /**
     * Create a company admin user.
     */
    public function companyAdmin(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('company-admin');
        });
    }

    /**
     * Create a branch manager user.
     */
    public function branchManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => \App\Models\Branch::factory(),
        ])->afterCreating(function ($user) {
            $user->assignRole('branch-manager');
        });
    }

    /**
     * Create a warehouse manager user.
     */
    public function warehouseManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => \App\Models\Branch::factory(),
        ])->afterCreating(function ($user) {
            $user->assignRole('warehouse-manager');
        });
    }

    /**
     * Create a warehouse operator user.
     */
    public function warehouseOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => \App\Models\Branch::factory(),
        ])->afterCreating(function ($user) {
            $user->assignRole('warehouse-operator');
        });
    }

    /**
     * Set specific company for the user.
     */
    public function forCompany($company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => is_object($company) ? $company->id : $company,
        ]);
    }

    /**
     * Set specific branch for the user.
     */
    public function forBranch($branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => is_object($branch) ? $branch->id : $branch,
        ]);
    }
}
