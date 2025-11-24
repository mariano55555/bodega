<?php

namespace Database\Seeders;

use App\Models\InventoryTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventoryTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $users = User::all();

        if ($warehouses->count() < 2) {
            $this->command->warn('Se necesitan al menos 2 almacenes activos para crear transferencias.');

            return;
        }

        $statuses = ['pending', 'in_transit', 'received', 'completed', 'cancelled'];
        $reasons = [
            'Restock de inventario bajo',
            'Balanceo de inventario entre almacenes',
            'Solicitud de sucursal',
            'Optimización de ubicación de productos',
            'Redistribución por demanda regional',
            'Transferencia de exceso de inventario',
            'Consolidación de productos de baja rotación',
            'Reorganización por estrategia de negocio',
            'Apoyo a almacén con alta demanda',
            'Centralización de productos especializados',
        ];

        $carriers = ['DHL Express', 'Servientrega', 'TCC', 'Coordinadora', 'Interrapidísimo', 'Envía'];

        $now = Carbon::now();

        // Create transfers with different statuses and dates
        $existingCount = InventoryTransfer::count();
        for ($i = $existingCount + 1; $i <= $existingCount + 30; $i++) {
            // Get random warehouses (different from each other)
            $fromWarehouse = $warehouses->random();
            $toWarehouse = $warehouses->where('id', '!=', $fromWarehouse->id)->random();

            $status = fake()->randomElement($statuses);
            $requestedUser = $users->random();

            // Create dates based on status
            $requestedAt = $now->copy()->subDays(rand(1, 30));
            $approvedAt = null;
            $shippedAt = null;
            $receivedAt = null;
            $completedAt = null;
            $cancelledAt = null;

            $approvedBy = null;
            $shippedBy = null;
            $receivedBy = null;

            switch ($status) {
                case 'pending':
                    // Only requested
                    break;

                case 'in_transit':
                    $approvedAt = $requestedAt->copy()->addHours(rand(2, 24));
                    $shippedAt = $approvedAt->copy()->addHours(rand(1, 8));
                    $approvedBy = $users->random()->id;
                    $shippedBy = $users->random()->id;
                    break;

                case 'received':
                    $approvedAt = $requestedAt->copy()->addHours(rand(2, 24));
                    $shippedAt = $approvedAt->copy()->addHours(rand(1, 8));
                    $receivedAt = $shippedAt->copy()->addHours(rand(4, 72));
                    $approvedBy = $users->random()->id;
                    $shippedBy = $users->random()->id;
                    $receivedBy = $users->random()->id;
                    break;

                case 'completed':
                    $approvedAt = $requestedAt->copy()->addHours(rand(2, 24));
                    $shippedAt = $approvedAt->copy()->addHours(rand(1, 8));
                    $receivedAt = $shippedAt->copy()->addHours(rand(4, 72));
                    $completedAt = $receivedAt->copy()->addHours(rand(1, 24));
                    $approvedBy = $users->random()->id;
                    $shippedBy = $users->random()->id;
                    $receivedBy = $users->random()->id;
                    break;

                case 'cancelled':
                    $cancelledAt = $requestedAt->copy()->addHours(rand(1, 48));
                    // Might be approved before cancellation
                    if (rand(0, 1)) {
                        $approvedAt = $requestedAt->copy()->addHours(rand(1, 12));
                        $approvedBy = $users->random()->id;
                    }
                    break;
            }

            $transfer = InventoryTransfer::create([
                'transfer_number' => 'TRF-'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'status' => $status,
                'reason' => fake()->randomElement($reasons),
                'notes' => fake()->optional(0.7)->paragraph(),
                'metadata' => json_encode([
                    'priority' => fake()->randomElement(['low', 'medium', 'high']),
                    'estimated_items' => rand(1, 50),
                    'category' => fake()->randomElement(['electronics', 'clothing', 'food', 'books', 'supplies']),
                ]),
                'requested_at' => $requestedAt,
                'approved_at' => $approvedAt,
                'shipped_at' => $shippedAt,
                'received_at' => $receivedAt,
                'completed_at' => $completedAt,
                'cancelled_at' => $cancelledAt,
                'requested_by' => $requestedUser->id,
                'approved_by' => $approvedBy,
                'approval_notes' => $approvedBy ? fake()->optional(0.5)->sentence() : null,
                'shipped_by' => $shippedBy,
                'tracking_number' => $shippedAt ? fake()->optional(0.8)->regexify('[A-Z0-9]{10,15}') : null,
                'carrier' => $shippedAt ? fake()->optional(0.6)->randomElement($carriers) : null,
                'shipping_cost' => $shippedAt ? fake()->optional(0.7)->randomFloat(2, 15000, 150000) : null,
                'received_by' => $receivedBy,
                'receiving_notes' => $receivedBy ? fake()->optional(0.4)->sentence() : null,
                'receiving_discrepancies' => $receivedBy && rand(0, 5) === 0 ? json_encode([
                    'missing_items' => rand(1, 3),
                    'damaged_items' => rand(0, 2),
                    'notes' => 'Algunos artículos llegaron dañados o faltantes',
                ]) : null,
                'is_active' => true,
                'active_at' => $requestedAt,
                'created_by' => $requestedUser->id,
                'created_at' => $requestedAt,
                'updated_at' => $completedAt ?? $receivedAt ?? $shippedAt ?? $approvedAt ?? $requestedAt,
            ]);

            $this->command->info("Transferencia creada: {$transfer->transfer_number} - {$status}");
        }

        $this->command->info('Se crearon 30 transferencias de inventario con diferentes estados.');
    }
}
