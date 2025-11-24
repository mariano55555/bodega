<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InventoryClosure;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventoryClosureSeeder extends Seeder
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

            // Create closures for the last 6 months
            $currentYear = now()->year;
            $currentMonth = now()->month;

            foreach ($warehouses as $warehouse) {
                // Create closures for last 6 months with different statuses
                for ($i = 6; $i >= 1; $i--) {
                    $date = now()->copy()->subMonths($i);

                    // Vary the status based on how old it is
                    if ($i >= 5) {
                        // Older months: closed
                        InventoryClosure::factory()
                            ->closed()
                            ->forPeriod($date->year, $date->month)
                            ->create([
                                'company_id' => $company->id,
                                'warehouse_id' => $warehouse->id,
                            ])
                            ->details()->createMany(
                                $this->generateDetails($products, rand(10, 20))
                            );
                    } elseif ($i == 4) {
                        // 4 months ago: reopened with discrepancies
                        InventoryClosure::factory()
                            ->reopened()
                            ->withDiscrepancies()
                            ->forPeriod($date->year, $date->month)
                            ->create([
                                'company_id' => $company->id,
                                'warehouse_id' => $warehouse->id,
                            ])
                            ->details()->createMany(
                                $this->generateDetailsWithDiscrepancies($products, rand(10, 15))
                            );
                    } elseif ($i == 3) {
                        // 3 months ago: cancelled
                        InventoryClosure::factory()
                            ->cancelled()
                            ->forPeriod($date->year, $date->month)
                            ->create([
                                'company_id' => $company->id,
                                'warehouse_id' => $warehouse->id,
                            ])
                            ->details()->createMany(
                                $this->generateDetails($products, rand(5, 10))
                            );
                    } elseif ($i == 2) {
                        // 2 months ago: closed
                        InventoryClosure::factory()
                            ->closed()
                            ->forPeriod($date->year, $date->month)
                            ->create([
                                'company_id' => $company->id,
                                'warehouse_id' => $warehouse->id,
                            ])
                            ->details()->createMany(
                                $this->generateDetails($products, rand(10, 20))
                            );
                    } else {
                        // 1 month ago: approved waiting to be closed
                        InventoryClosure::factory()
                            ->approved()
                            ->forPeriod($date->year, $date->month)
                            ->create([
                                'company_id' => $company->id,
                                'warehouse_id' => $warehouse->id,
                            ])
                            ->details()->createMany(
                                $this->generateDetails($products, rand(12, 20))
                            );
                    }
                }

                // Create current month closure (in process)
                InventoryClosure::factory()
                    ->inProcess()
                    ->forPeriod($currentYear, $currentMonth)
                    ->create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouse->id,
                    ])
                    ->details()->createMany(
                        $this->generateDetails($products, rand(15, 25))
                    );
            }

            $this->command->info("Created inventory closures for company: {$company->name}");
        }
    }

    /**
     * Generate closure details for random products
     */
    private function generateDetails($products, int $count): array
    {
        $details = [];
        $selectedProducts = $products->random(min($count, $products->count()));

        foreach ($selectedProducts as $product) {
            $openingQty = fake()->randomFloat(4, 50, 1000);
            $openingCost = fake()->randomFloat(2, 10, 500);

            $qtyIn = fake()->randomFloat(4, 0, 200);
            $qtyOut = fake()->randomFloat(4, 0, 150);
            $movementCount = fake()->numberBetween(5, 50);

            $calculatedQty = $openingQty + $qtyIn - $qtyOut;
            $calculatedCost = $openingCost;
            $calculatedValue = $calculatedQty * $calculatedCost;

            $details[] = [
                'product_id' => $product->id,
                'opening_quantity' => $openingQty,
                'opening_unit_cost' => $openingCost,
                'opening_total_value' => $openingQty * $openingCost,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'movement_count' => $movementCount,
                'calculated_closing_quantity' => $calculatedQty,
                'calculated_closing_unit_cost' => $calculatedCost,
                'calculated_closing_value' => $calculatedValue,
                'adjusted_closing_quantity' => $calculatedQty,
                'adjusted_closing_unit_cost' => $calculatedCost,
                'adjusted_closing_value' => $calculatedValue,
                'is_adjusted' => false,
                'below_minimum' => fake()->boolean(20),
                'above_maximum' => fake()->boolean(10),
                'needs_reorder' => fake()->boolean(25),
                'notes' => fake()->optional(0.3)->sentence(),
            ];
        }

        return $details;
    }

    /**
     * Generate closure details with discrepancies
     */
    private function generateDetailsWithDiscrepancies($products, int $count): array
    {
        $details = [];
        $selectedProducts = $products->random(min($count, $products->count()));

        foreach ($selectedProducts as $product) {
            $openingQty = fake()->randomFloat(4, 50, 1000);
            $openingCost = fake()->randomFloat(2, 10, 500);

            $qtyIn = fake()->randomFloat(4, 0, 200);
            $qtyOut = fake()->randomFloat(4, 0, 150);
            $movementCount = fake()->numberBetween(5, 50);

            $calculatedQty = $openingQty + $qtyIn - $qtyOut;
            $calculatedCost = $openingCost;
            $calculatedValue = $calculatedQty * $calculatedCost;

            // Add discrepancy for some items (60% chance)
            $hasDiscrepancy = fake()->boolean(60);
            $physicalQty = $hasDiscrepancy
                ? $calculatedQty + fake()->randomFloat(2, -50, 50)
                : $calculatedQty;

            $physicalValue = $physicalQty * $calculatedCost;
            $discrepancyQty = $physicalQty - $calculatedQty;
            $discrepancyValue = $physicalValue - $calculatedValue;

            $details[] = [
                'product_id' => $product->id,
                'opening_quantity' => $openingQty,
                'opening_unit_cost' => $openingCost,
                'opening_total_value' => $openingQty * $openingCost,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'movement_count' => $movementCount,
                'calculated_closing_quantity' => $calculatedQty,
                'calculated_closing_unit_cost' => $calculatedCost,
                'calculated_closing_value' => $calculatedValue,
                'physical_count_quantity' => $physicalQty,
                'physical_count_unit_cost' => $calculatedCost,
                'physical_count_value' => $physicalValue,
                'physical_count_date' => now()->subDays(rand(1, 5)),
                'discrepancy_quantity' => $discrepancyQty,
                'discrepancy_value' => $discrepancyValue,
                'has_discrepancy' => $hasDiscrepancy,
                'discrepancy_notes' => $hasDiscrepancy ? fake()->sentence() : null,
                'adjusted_closing_quantity' => $physicalQty,
                'adjusted_closing_unit_cost' => $calculatedCost,
                'adjusted_closing_value' => $physicalValue,
                'is_adjusted' => $hasDiscrepancy,
                'adjustment_notes' => $hasDiscrepancy ? fake()->sentence() : null,
                'below_minimum' => fake()->boolean(20),
                'above_maximum' => fake()->boolean(10),
                'needs_reorder' => fake()->boolean(25),
                'notes' => fake()->optional(0.3)->sentence(),
            ];
        }

        return $details;
    }
}
