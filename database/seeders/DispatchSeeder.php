<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DispatchSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $customers = Customer::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        $users = User::all();

        if ($warehouses->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No hay almacenes o productos activos.');

            return;
        }

        $now = Carbon::now();
        $dispatchTypes = ['venta', 'interno', 'externo', 'donacion'];
        $statuses = ['borrador', 'pendiente', 'aprobado', 'despachado', 'entregado', 'cancelado'];

        for ($i = 1; $i <= 40; $i++) {
            $warehouse = $warehouses->random();
            $company = $warehouse->company;
            $user = $users->random();
            $dispatchType = fake()->randomElement($dispatchTypes);

            $customer = in_array($dispatchType, ['venta', 'externo']) && $customers->isNotEmpty()
                ? $customers->where('company_id', $company->id)->random()
                : null;

            $status = fake()->randomElement($statuses);
            $createdAt = $now->copy()->subDays(rand(1, 45));

            $dispatch = Dispatch::create([
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'customer_id' => $customer?->id,
                'dispatch_number' => 'DSP-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'dispatch_type' => $dispatchType,
                'recipient_name' => $customer?->contact_person ?? fake()->name(),
                'recipient_phone' => $customer?->contact_phone ?? fake()->phoneNumber(),
                'recipient_email' => $customer?->contact_email ?? fake()->optional(0.7)->email(),
                'delivery_address' => $customer?->address ?? fake()->address(),
                'shipping_cost' => fake()->randomFloat(2, 0, 300),
                'status' => $status,
                'notes' => fake()->optional(0.5)->sentence(),
                'is_active' => true,
                'active_at' => $createdAt,
                'created_by' => $user->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            for ($j = 0; $j < rand(1, 6); $j++) {
                $product = $products->random();
                DispatchDetail::create([
                    'dispatch_id' => $dispatch->id,
                    'product_id' => $product->id,
                    'quantity' => fake()->randomFloat(2, 1, 100),
                    'unit_of_measure_id' => $product->unit_of_measure_id,
                    'unit_price' => fake()->randomFloat(2, 50, 3000),
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }

            $dispatch->calculateTotals();

            if ($i % 10 === 0) {
                $this->command->info("Creados {$i} despachos...");
            }
        }

        $this->command->info('Se crearon 40 despachos con detalles.');
    }
}
