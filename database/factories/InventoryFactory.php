<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(4, 10, 1000);
        $reservedQuantity = $this->faker->randomFloat(4, 0, $quantity * 0.3);
        $unitCost = $this->faker->randomFloat(4, 0.50, 200);

        return [
            'product_id' => \App\Models\Product::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $quantity - $reservedQuantity,
            'location' => $this->faker->bothify('A##-B##-C##'),
            'lot_number' => $this->faker->bothify('LOT-##??-####'),
            'expiration_date' => $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+2 years'),
            'unit_cost' => $unitCost,
            'total_value' => $quantity * $unitCost,
            'is_active' => true,
            'active_at' => now(),
            'last_count_quantity' => $quantity,
            'last_counted_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'last_counted_by' => \App\Models\User::factory(),
            'created_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Create inventory with no stock.
     */
    public function outOfStock(): static
    {
        return $this->state([
            'quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
            'total_value' => 0,
        ]);
    }

    /**
     * Create inventory with high reserved quantity.
     */
    public function highlyReserved(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'] ?? $this->faker->randomFloat(4, 100, 500);
            $reservedQuantity = $quantity * 0.8; // 80% reserved

            return [
                'quantity' => $quantity,
                'reserved_quantity' => $reservedQuantity,
                'available_quantity' => $quantity - $reservedQuantity,
                'total_value' => $quantity * ($attributes['unit_cost'] ?? 10),
            ];
        });
    }

    /**
     * Create inventory that is expiring soon.
     */
    public function expiringSoon(int $days = 30): static
    {
        return $this->state([
            'expiration_date' => $this->faker->dateTimeBetween('now', "+{$days} days"),
        ]);
    }

    /**
     * Create inventory that is already expired.
     */
    public function expired(): static
    {
        return $this->state([
            'expiration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Create inventory without expiration date.
     */
    public function nonPerishable(): static
    {
        return $this->state([
            'expiration_date' => null,
        ]);
    }

    /**
     * Create inventory with specific lot.
     */
    public function withLot(string $lotNumber, ?\Carbon\Carbon $expirationDate = null): static
    {
        return $this->state([
            'lot_number' => $lotNumber,
            'expiration_date' => $expirationDate,
        ]);
    }

    /**
     * Create inventory below minimum stock threshold.
     */
    public function belowMinimum(): static
    {
        return $this->state([
            'quantity' => $this->faker->randomFloat(4, 1, 5),
            'available_quantity' => $this->faker->randomFloat(4, 1, 5),
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Create inventory with discrepancy in last count.
     */
    public function withCountDiscrepancy(): static
    {
        return $this->state(function (array $attributes) {
            $currentQuantity = $attributes['quantity'] ?? $this->faker->randomFloat(4, 50, 200);
            $countQuantity = $currentQuantity + $this->faker->randomFloat(4, -10, 10);

            return [
                'quantity' => $currentQuantity,
                'last_count_quantity' => $countQuantity,
                'last_counted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Create inactive inventory.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Create inventory with specific location.
     */
    public function atLocation(string $location): static
    {
        return $this->state([
            'location' => $location,
        ]);
    }

    /**
     * Create inventory for FIFO testing (oldest first).
     */
    public function fifoOldest(): static
    {
        return $this->state([
            'lot_number' => 'LOT-OLD-'.$this->faker->numerify('####'),
            'expiration_date' => $this->faker->dateTimeBetween('+1 month', '+3 months'),
            'created_at' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
        ]);
    }

    /**
     * Create inventory for FIFO testing (newest).
     */
    public function fifoNewest(): static
    {
        return $this->state([
            'lot_number' => 'LOT-NEW-'.$this->faker->numerify('####'),
            'expiration_date' => $this->faker->dateTimeBetween('+6 months', '+1 year'),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
