<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $storageLocations = StorageLocation::where('is_active', true)->get();
        $users = User::all();

        if ($products->isEmpty()) {
            $this->command->warn('No hay productos activos para crear inventario.');

            return;
        }

        if ($warehouses->isEmpty()) {
            $this->command->warn('No hay almacenes activos para crear inventario.');

            return;
        }

        $now = Carbon::now();
        $createdCount = 0;

        // Crear inventario para cada combinación de producto-almacén (con probabilidad)
        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                // 70% de probabilidad de que un producto esté en un almacén
                if (rand(0, 100) <= 70) {
                    $storageLocation = $storageLocations->where('warehouse_id', $warehouse->id)->first();

                    // Generar datos realistas de inventario
                    $quantity = fake()->randomFloat(2, 0, 1000);
                    $reservedQuantity = $quantity > 0 ? fake()->randomFloat(2, 0, $quantity * 0.3) : 0;
                    $availableQuantity = $quantity - $reservedQuantity;

                    $unitCost = fake()->randomFloat(2, 1000, 50000); // En colones
                    $totalValue = $quantity * $unitCost;

                    // Fechas de conteo aleatorias en los últimos 90 días
                    $lastCountedAt = fake()->optional(0.8)->dateTimeBetween('-90 days', 'now');
                    $lastCountQuantity = $lastCountedAt ? fake()->randomFloat(2, $quantity * 0.8, $quantity * 1.2) : null;

                    Inventory::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'storage_location_id' => $storageLocation?->id,
                        'quantity' => $quantity,
                        'reserved_quantity' => $reservedQuantity,
                        'available_quantity' => $availableQuantity,
                        'location' => fake()->optional(0.6)->randomElement(['A1-01', 'B2-05', 'C3-10', 'D1-15', 'E2-20']),
                        'lot_number' => fake()->optional(0.7)->regexify('LOT[0-9]{6}'),
                        'expiration_date' => fake()->optional(0.4)->dateTimeBetween('now', '+2 years'),
                        'unit_cost' => $unitCost,
                        'total_value' => $totalValue,
                        'is_active' => true,
                        'active_at' => $now->copy()->subDays(rand(1, 365)),
                        'last_count_quantity' => $lastCountQuantity,
                        'last_counted_at' => $lastCountedAt,
                        'last_counted_by' => $lastCountedAt ? $users->random()->id : null,
                        'created_by' => $users->random()->id,
                        'created_at' => $now->copy()->subDays(rand(1, 180)),
                        'updated_at' => $now->copy()->subDays(rand(0, 30)),
                    ]);

                    $createdCount++;
                }
            }
        }

        $this->command->info("Se crearon {$createdCount} registros de inventario.");
    }
}
