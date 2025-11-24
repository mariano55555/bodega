<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductLot>
 */
class ProductLotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lotNumber = $this->generateLotNumber();
        $quantityProduced = $this->faker->randomFloat(4, 100, 10000);
        $manufacturedDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $expirationDate = $this->faker->optional(0.8)->dateTimeBetween(
            $manufacturedDate,
            (clone $manufacturedDate)->modify('+2 years')
        );

        return [
            'product_id' => Product::factory(),
            'supplier_id' => $this->faker->optional(0.7)->randomElement([
                Supplier::factory(),
                fn () => Supplier::inRandomOrder()->first()?->id,
            ]),
            'lot_number' => $lotNumber,
            'slug' => Str::slug($lotNumber),
            'manufactured_date' => $manufacturedDate,
            'expiration_date' => $expirationDate,
            'quantity_produced' => $quantityProduced,
            'quantity_remaining' => $this->faker->randomFloat(4, 0, $quantityProduced),
            'unit_cost' => $this->faker->randomFloat(4, 0.50, 500.00),
            'status' => $this->faker->randomElement(['active', 'expired', 'quarantine', 'disposed']),
            'batch_certificate' => $this->faker->optional(0.3)->bothify('CERT-####-??##'),
            'quality_attributes' => $this->faker->optional(0.4)->randomElement([
                ['color' => 'azul', 'texture' => 'suave', 'grade' => 'A'],
                ['temperature' => '20°C', 'humidity' => '65%', 'ph' => '7.2'],
                ['purity' => '99.5%', 'density' => '1.2 g/cm³'],
                null,
            ]),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.2)->randomElement([
                ['origin' => 'Nacional', 'certification' => 'ISO 9001'],
                ['storage_temp' => '15-25°C', 'special_handling' => true],
                null,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_by' => $this->faker->optional(0.8)->randomElement([
                User::factory(),
                fn () => User::inRandomOrder()->first()?->id,
            ]),
        ];
    }

    /**
     * Generate a realistic lot number.
     */
    private function generateLotNumber(): string
    {
        $patterns = [
            'LOT-{date}-{sequence}',
            'L{year}{month}{day}-{alpha}{numeric}',
            'BAT{year}-{sequence:4}',
            '{year}{month}-{alpha:2}{numeric:4}',
        ];

        $pattern = $this->faker->randomElement($patterns);
        $date = $this->faker->dateTimeBetween('-1 year', 'now');

        return str_replace([
            '{date}' => $date->format('Ymd'),
            '{year}' => $date->format('Y'),
            '{month}' => $date->format('m'),
            '{day}' => $date->format('d'),
            '{sequence}' => str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            '{sequence:4}' => str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            '{alpha}' => $this->faker->randomLetter(),
            '{alpha:2}' => $this->faker->lexify('??'),
            '{numeric}' => $this->faker->randomDigit(),
            '{numeric:4}' => $this->faker->numerify('####'),
        ], $pattern);
    }

    /**
     * Create an active lot with available quantity.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $quantityProduced = $attributes['quantity_produced'] ?? $this->faker->randomFloat(4, 100, 5000);

            return [
                'status' => 'active',
                'quantity_remaining' => $this->faker->randomFloat(4, 1, $quantityProduced),
                'is_active' => true,
                'active_at' => now(),
            ];
        });
    }

    /**
     * Create an expired lot.
     */
    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'expiration_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'quantity_remaining' => $this->faker->randomFloat(4, 0, 100),
        ]);
    }

    /**
     * Create a lot expiring soon.
     */
    public function expiringSoon(int $days = 30): static
    {
        return $this->state([
            'status' => 'active',
            'expiration_date' => $this->faker->dateTimeBetween('now', "+{$days} days"),
            'quantity_remaining' => $this->faker->randomFloat(4, 1, 1000),
        ]);
    }

    /**
     * Create a lot in quarantine.
     */
    public function quarantine(): static
    {
        return $this->state([
            'status' => 'quarantine',
            'notes' => 'Lote en cuarentena - '.$this->faker->sentence(),
            'quantity_remaining' => $this->faker->randomFloat(4, 1, 1000),
        ]);
    }

    /**
     * Create a disposed lot.
     */
    public function disposed(): static
    {
        return $this->state([
            'status' => 'disposed',
            'quantity_remaining' => 0,
            'notes' => 'Lote dispuesto - '.$this->faker->sentence(),
        ]);
    }

    /**
     * Create a lot with full quantity available.
     */
    public function fullQuantity(): static
    {
        return $this->state(function (array $attributes) {
            $quantityProduced = $attributes['quantity_produced'] ?? $this->faker->randomFloat(4, 100, 5000);

            return [
                'quantity_remaining' => $quantityProduced,
                'status' => 'active',
            ];
        });
    }

    /**
     * Create a lot with minimal remaining quantity.
     */
    public function lowQuantity(): static
    {
        return $this->state(function (array $attributes) {
            $quantityProduced = $attributes['quantity_produced'] ?? $this->faker->randomFloat(4, 100, 1000);

            return [
                'quantity_remaining' => $this->faker->randomFloat(4, 0.1, min(10, $quantityProduced * 0.1)),
                'status' => 'active',
            ];
        });
    }

    /**
     * Create a lot with specific product.
     */
    public function forProduct(int $productId): static
    {
        return $this->state([
            'product_id' => $productId,
        ]);
    }

    /**
     * Create a lot with specific supplier.
     */
    public function fromSupplier(int $supplierId): static
    {
        return $this->state([
            'supplier_id' => $supplierId,
        ]);
    }

    /**
     * Create a lot with specific dates.
     */
    public function withDates(\Carbon\Carbon $manufactured, ?\Carbon\Carbon $expiration = null): static
    {
        return $this->state([
            'manufactured_date' => $manufactured,
            'expiration_date' => $expiration,
        ]);
    }

    /**
     * Create a lot with quality certification.
     */
    public function certified(): static
    {
        return $this->state([
            'batch_certificate' => $this->faker->bothify('CERT-####-??##'),
            'quality_attributes' => [
                'certified' => true,
                'certification_type' => $this->faker->randomElement(['ISO 9001', 'HACCP', 'FDA', 'CE']),
                'quality_grade' => $this->faker->randomElement(['A', 'B', 'Premium', 'Standard']),
                'test_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Create a lot with specific cost.
     */
    public function withCost(float $unitCost): static
    {
        return $this->state([
            'unit_cost' => $unitCost,
        ]);
    }

    /**
     * Create a lot for FIFO testing (older manufacturing date).
     */
    public function fifoFirst(): static
    {
        return $this->state([
            'manufactured_date' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
            'status' => 'active',
            'quantity_remaining' => $this->faker->randomFloat(4, 10, 500),
        ]);
    }

    /**
     * Create a lot for FEFO testing (earlier expiration date).
     */
    public function fefoFirst(): static
    {
        return $this->state([
            'expiration_date' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'status' => 'active',
            'quantity_remaining' => $this->faker->randomFloat(4, 10, 500),
        ]);
    }
}
