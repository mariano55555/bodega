<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Donation;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please seed companies first.');

            return;
        }

        foreach ($companies as $company) {
            // Get company's warehouses and products
            $warehouses = Warehouse::where('company_id', $company->id)->get();
            $products = Product::where('company_id', $company->id)->get();

            if ($warehouses->isEmpty() || $products->isEmpty()) {
                $this->command->warn("Skipping company {$company->name} - no warehouses or products found.");

                continue;
            }

            // Create draft donations (3 per company)
            foreach (range(1, 3) as $i) {
                Donation::factory()
                    ->draft()
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouses->random()->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(2, 5))
                    );
            }

            // Create pending donations (2 per company)
            foreach (range(1, 2) as $i) {
                Donation::factory()
                    ->pending()
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouses->random()->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(2, 4))
                    );
            }

            // Create approved donations (3 per company)
            foreach (range(1, 3) as $i) {
                Donation::factory()
                    ->approved()
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouses->random()->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(3, 6))
                    );
            }

            // Create received donations (10 per company)
            foreach (range(1, 10) as $i) {
                Donation::factory()
                    ->received()
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouses->random()->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(2, 7))
                    );
            }

            // Create cancelled donations (1 per company)
            Donation::factory()
                ->cancelled()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                ])
                ->details()->createMany(
                    $this->generateDetails($products, rand(1, 3))
                );

            // Create some donations with specific donor types
            // Individual donors (2 per company)
            foreach (range(1, 2) as $i) {
                Donation::factory()
                    ->received()
                    ->fromIndividual()
                    ->withTaxReceipt()
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouses->random()->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(1, 3))
                    );
            }

            // Organization donors (2 per company)
            foreach (range(1, 2) as $i) {
                Donation::factory()
                    ->received()
                    ->fromOrganization()
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouses->random()->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(3, 8))
                    );
            }

            // Government donors (1 per company)
            Donation::factory()
                ->received()
                ->fromGovernment()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                ])
                ->details()->createMany(
                    $this->generateDetails($products, rand(5, 10))
                );

            $this->command->info("Created donations for company: {$company->name}");
        }
    }

    /**
     * Generate donation details for random products
     */
    private function generateDetails($products, int $count): array
    {
        $details = [];
        $selectedProducts = $products->random(min($count, $products->count()));

        foreach ($selectedProducts as $product) {
            $quantity = fake()->randomFloat(4, 1, 100);
            $unitValue = fake()->randomFloat(2, 5, 500);

            $details[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'estimated_unit_value' => $unitValue,
                'condition' => fake()->randomElement(['nuevo', 'usado', 'reacondicionado']),
                'condition_notes' => fake()->optional(0.3)->sentence(),
                'lot_number' => fake()->optional(0.5)->regexify('LOT-[0-9]{4}-[A-Z]{2}'),
                'expiration_date' => fake()->optional(0.4)->dateTimeBetween('+6 months', '+2 years'),
                'notes' => fake()->optional(0.3)->sentence(),
            ];
        }

        return $details;
    }
}
