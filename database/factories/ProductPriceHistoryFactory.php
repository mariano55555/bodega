<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductPriceHistory>
 */
class ProductPriceHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldCost = fake()->randomFloat(5, 1, 1000);
        $newCost = fake()->randomFloat(5, 1, 1000);
        $costChange = $newCost - $oldCost;
        $changePercentage = $oldCost > 0 ? round(($costChange / $oldCost) * 100, 2) : 0;

        return [
            'product_id' => Product::factory(),
            'old_cost' => $oldCost,
            'new_cost' => $newCost,
            'cost_change' => $costChange,
            'change_percentage' => $changePercentage,
            'source_type' => 'purchase',
            'source_id' => null,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'active_at' => now(),
        ];
    }
}
