<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventoryAdjustmentSeeder extends Seeder
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

            // Create draft adjustments (5 per company)
            InventoryAdjustment::factory()
                ->count(5)
                ->draft()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Create pending adjustments (3 per company)
            InventoryAdjustment::factory()
                ->count(3)
                ->pending()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Create approved adjustments (5 per company)
            InventoryAdjustment::factory()
                ->count(5)
                ->approved()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Create processed adjustments (10 per company)
            InventoryAdjustment::factory()
                ->count(10)
                ->processed()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Create some rejected adjustments (2 per company)
            InventoryAdjustment::factory()
                ->count(2)
                ->rejected()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Create cancelled adjustments (1 per company)
            InventoryAdjustment::factory()
                ->cancelled()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Create some specific adjustment types
            // Damaged products
            InventoryAdjustment::factory()
                ->count(2)
                ->damage()
                ->processed()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            // Expired products
            InventoryAdjustment::factory()
                ->count(2)
                ->expiry()
                ->processed()
                ->create([
                    'company_id' => $company->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'product_id' => $products->random()->id,
                ]);

            $this->command->info("Created inventory adjustments for company: {$company->name}");
        }

        $totalAdjustments = InventoryAdjustment::count();
        $this->command->info("Total inventory adjustments created: {$totalAdjustments}");
    }
}
