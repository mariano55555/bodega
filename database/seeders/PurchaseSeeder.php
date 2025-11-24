<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        $users = User::all();

        if ($suppliers->isEmpty()) {
            $this->command->warn('No hay proveedores activos para crear compras.');

            return;
        }

        if ($warehouses->isEmpty()) {
            $this->command->warn('No hay almacenes activos para crear compras.');

            return;
        }

        if ($products->isEmpty()) {
            $this->command->warn('No hay productos activos para crear compras.');

            return;
        }

        $now = Carbon::now();
        $documentTypes = ['factura', 'ccf', 'ticket', 'otro'];
        $purchaseTypes = ['efectivo', 'credito'];
        $paymentStatuses = ['pendiente', 'parcial', 'pagado'];
        $paymentMethods = ['efectivo', 'transferencia', 'cheque', 'tarjeta'];
        $fundSources = ['caja_chica', 'cuenta_corriente', 'linea_credito'];
        $statuses = ['borrador', 'pendiente', 'aprobado', 'recibido', 'cancelado'];

        // Crear 50 compras diversas
        for ($i = 1; $i <= 50; $i++) {
            $supplier = $suppliers->random();
            $warehouse = $warehouses->random();
            $company = $warehouse->company;
            $user = $users->random();

            $status = fake()->randomElement($statuses);
            $purchaseType = fake()->randomElement($purchaseTypes);
            $paymentStatus = $status === 'recibido' ? fake()->randomElement(['pendiente', 'parcial', 'pagado']) : 'pendiente';

            $documentDate = $now->copy()->subDays(rand(1, 60));
            $dueDate = $purchaseType === 'credito' ? $documentDate->copy()->addDays(rand(15, 90)) : null;

            $approvedAt = null;
            $approvedBy = null;
            $receivedAt = null;
            $receivedBy = null;

            if (in_array($status, ['aprobado', 'recibido'])) {
                $approvedAt = $documentDate->copy()->addHours(rand(1, 48));
                $approvedBy = $users->random()->id;

                if ($status === 'recibido') {
                    $receivedAt = $approvedAt->copy()->addHours(rand(2, 72));
                    $receivedBy = $users->random()->id;
                }
            }

            $purchase = Purchase::create([
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id,
                'purchase_number' => 'COM-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'document_type' => fake()->randomElement($documentTypes),
                'document_number' => fake()->regexify('[A-Z]{2}-[0-9]{3}-[0-9]{8}'),
                'document_date' => $documentDate,
                'due_date' => $dueDate,
                'purchase_type' => $purchaseType,
                'payment_status' => $paymentStatus,
                'payment_method' => fake()->randomElement($paymentMethods),
                'fund_source' => fake()->randomElement($fundSources),
                'shipping_cost' => fake()->randomFloat(2, 0, 500),
                'status' => $status,
                'approved_at' => $approvedAt,
                'approved_by' => $approvedBy,
                'received_at' => $receivedAt,
                'received_by' => $receivedBy,
                'notes' => fake()->optional(0.6)->sentence(),
                'admin_notes' => fake()->optional(0.3)->sentence(),
                'is_active' => true,
                'active_at' => $documentDate,
                'created_by' => $user->id,
                'created_at' => $documentDate,
                'updated_at' => $receivedAt ?? $approvedAt ?? $documentDate,
            ]);

            // Crear entre 1 y 8 detalles de compra
            $detailCount = rand(1, 8);
            for ($j = 0; $j < $detailCount; $j++) {
                $product = $products->random();
                $quantity = fake()->randomFloat(2, 1, 500);
                $unitCost = fake()->randomFloat(2, 10, 5000);
                $discountPercentage = fake()->optional(0.4)->randomFloat(2, 0, 20) ?? 0;
                $taxPercentage = 13.00; // IVA en El Salvador

                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'discount_percentage' => $discountPercentage,
                    'tax_percentage' => $taxPercentage,
                    'lot_number' => fake()->optional(0.4)->regexify('LOT[0-9]{8}'),
                    'expiration_date' => fake()->optional(0.3)->dateTimeBetween('+6 months', '+3 years'),
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }

            // Recalcular totales de la compra
            $purchase->calculateTotals();

            if ($i % 10 === 0) {
                $this->command->info("Creadas {$i} compras...");
            }
        }

        $this->command->info('Se crearon 50 compras con detalles, diferentes proveedores, estados y tipos de pago.');
    }
}
