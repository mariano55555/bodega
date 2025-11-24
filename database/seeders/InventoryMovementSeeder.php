<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $storageLocations = StorageLocation::where('is_active', true)->get();
        $movementReasons = MovementReason::where('is_active', true)->get();
        $transfers = InventoryTransfer::all();
        $users = User::all();

        if ($products->isEmpty()) {
            $this->command->warn('No hay productos activos para crear movimientos.');

            return;
        }

        if ($warehouses->isEmpty()) {
            $this->command->warn('No hay almacenes activos para crear movimientos.');

            return;
        }

        if ($movementReasons->isEmpty()) {
            $this->command->warn('No hay razones de movimiento para crear movimientos.');

            return;
        }

        $now = Carbon::now();
        $movementTypes = ['purchase', 'sale', 'transfer_out', 'transfer_in', 'adjustment', 'return', 'damage', 'production', 'count'];
        $statuses = ['pending', 'approved', 'completed', 'cancelled', 'rejected'];
        $documentTypes = ['Factura', 'Orden de Compra', 'Nota de Entrega', 'Transferencia', 'Ajuste Manual'];

        // Crear 200 movimientos diversos
        for ($i = 1; $i <= 200; $i++) {
            $product = $products->random();
            $warehouse = $warehouses->random();
            $movementType = fake()->randomElement($movementTypes);
            $movementReason = $movementReasons->random();
            $user = $users->random();

            // Cantidades base
            $quantity = fake()->randomFloat(2, 1, 100);
            $previousQuantity = fake()->randomFloat(2, 0, 500);

            // Calcular nueva cantidad según tipo de movimiento
            $newQuantity = match ($movementType) {
                'purchase', 'transfer_in', 'return', 'production' => $previousQuantity + $quantity,
                'sale', 'transfer_out', 'damage', 'theft', 'expiry' => max(0, $previousQuantity - $quantity),
                'adjustment', 'count' => fake()->randomFloat(2, 0, 600),
                default => $previousQuantity
            };

            $unitCost = fake()->randomFloat(2, 1000, 50000); // En colones
            $totalCost = $quantity * $unitCost;

            // Almacenes de origen y destino para transferencias
            $fromWarehouseId = null;
            $toWarehouseId = null;
            $transferId = null;

            if (in_array($movementType, ['transfer_out', 'transfer_in']) && rand(0, 100) <= 30) {
                if ($movementType === 'transfer_out') {
                    $fromWarehouseId = $warehouse->id;
                    $toWarehouseId = $warehouses->where('id', '!=', $warehouse->id)->random()->id;
                } else {
                    $toWarehouseId = $warehouse->id;
                    $fromWarehouseId = $warehouses->where('id', '!=', $warehouse->id)->random()->id;
                }
                $transferId = $transfers->isNotEmpty() ? $transfers->random()->id : null;
            }

            // Ubicaciones de almacenamiento
            $storageLocation = $storageLocations->where('warehouse_id', $warehouse->id)->first();
            $fromStorageLocationId = $fromWarehouseId ? $storageLocations->where('warehouse_id', $fromWarehouseId)->first()?->id : null;
            $toStorageLocationId = $toWarehouseId ? $storageLocations->where('warehouse_id', $toWarehouseId)->first()?->id : null;

            $status = fake()->randomElement($statuses);
            $createdAt = $now->copy()->subDays(rand(1, 90));

            // Fechas basadas en el estado
            $confirmedAt = null;
            $approvedAt = null;
            $completedAt = null;
            $rejectedAt = null;
            $scheduledAt = null;
            $confirmedBy = null;
            $approvedBy = null;
            $rejectedBy = null;
            $completedBy = null;

            switch ($status) {
                case 'approved':
                case 'completed':
                    $confirmedAt = $createdAt->copy()->addMinutes(rand(30, 480));
                    $confirmedBy = $users->random()->id;
                    $approvedAt = $confirmedAt->copy()->addMinutes(rand(15, 240));
                    $approvedBy = $users->random()->id;

                    if ($status === 'completed') {
                        $completedAt = $approvedAt->copy()->addMinutes(rand(30, 360));
                        $completedBy = $users->random()->id;
                    }
                    break;

                case 'rejected':
                    $rejectedAt = $createdAt->copy()->addMinutes(rand(15, 720));
                    $rejectedBy = $users->random()->id;
                    break;

                case 'pending':
                    $scheduledAt = fake()->optional(0.3)->dateTimeBetween($createdAt, $createdAt->copy()->addDays(7));
                    break;
            }

            InventoryMovement::create([
                'product_id' => $product->id,
                'product_lot_id' => null, // Se puede agregar después si hay lotes
                'warehouse_id' => $warehouse->id,
                'movement_type' => $movementType,
                'movement_reason_id' => $movementReason->id,
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_number' => 'MOV-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'notes' => fake()->optional(0.6)->paragraph(),
                'lot_number' => fake()->optional(0.4)->regexify('LOT[0-9]{6}'),
                'batch_number' => fake()->optional(0.3)->regexify('BATCH[0-9]{4}'),
                'expiration_date' => fake()->optional(0.3)->dateTimeBetween('now', '+2 years'),
                'location' => fake()->optional(0.5)->randomElement(['A1-01', 'B2-05', 'C3-10', 'D1-15', 'E2-20']),
                'from_warehouse_id' => $fromWarehouseId,
                'from_storage_location_id' => $fromStorageLocationId,
                'to_warehouse_id' => $toWarehouseId,
                'to_storage_location_id' => $toStorageLocationId,
                'transfer_id' => $transferId,
                'document_type' => fake()->randomElement($documentTypes),
                'document_number' => fake()->optional(0.8)->regexify('[A-Z]{2}[0-9]{6}'),
                'metadata' => json_encode([
                    'priority' => fake()->randomElement(['low', 'medium', 'high']),
                    'category' => fake()->randomElement(['purchase', 'sale', 'return', 'adjustment', 'transfer']),
                    'temperature_required' => fake()->optional(0.2)->randomElement(['cold', 'frozen', 'ambient']),
                ]),
                'movement_data' => json_encode([
                    'weight' => fake()->optional(0.6)->randomFloat(2, 0.1, 50),
                    'dimensions' => fake()->optional(0.4)->randomElement(['10x10x10', '20x15x8', '30x25x15']),
                    'handling_instructions' => fake()->optional(0.3)->sentence(),
                ]),
                'status' => $status,
                'is_confirmed' => in_array($status, ['approved', 'completed']),
                'requires_quality_check' => fake()->boolean(30),
                'quality_approved' => $status === 'completed' ? fake()->boolean(90) : null,
                'confirmed_at' => $confirmedAt,
                'approved_at' => $approvedAt,
                'approval_notes' => $approvedBy ? fake()->optional(0.4)->sentence() : null,
                'rejected_at' => $rejectedAt,
                'scheduled_at' => $scheduledAt,
                'completed_at' => $completedAt,
                'quality_checked_at' => $status === 'completed' ? $completedAt : null,
                'quality_notes' => $status === 'completed' ? fake()->optional(0.3)->sentence() : null,
                'rejection_reason' => $rejectedAt ? fake()->sentence() : null,
                'confirmed_by' => $confirmedBy,
                'approved_by' => $approvedBy,
                'rejected_by' => $rejectedBy,
                'completed_by' => $completedBy,
                'quality_checked_by' => $status === 'completed' ? $completedBy : null,
                'is_active' => true,
                'active_at' => $createdAt,
                'created_by' => $user->id,
                'created_at' => $createdAt,
                'updated_at' => $completedAt ?? $rejectedAt ?? $approvedAt ?? $confirmedAt ?? $createdAt,
            ]);

            if ($i % 50 === 0) {
                $this->command->info("Creados {$i} movimientos...");
            }
        }

        $this->command->info('Se crearon 200 movimientos de inventario con diferentes tipos y estados.');
    }
}
