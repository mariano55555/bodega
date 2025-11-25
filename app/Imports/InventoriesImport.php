<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InventoriesImport implements SkipsOnError, SkipsOnFailure, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    protected int $companyId;

    protected int $userId;

    protected array $importErrors = [];

    protected int $successCount = 0;

    protected int $skippedCount = 0;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                try {
                    $this->processRow($row, $index + 2);
                } catch (\Exception $e) {
                    $this->importErrors[] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                        'data' => $row->toArray(),
                    ];
                    $this->skippedCount++;
                }
            }
        });
    }

    protected function processRow(Collection $row, int $rowNumber): void
    {
        // Validate required fields
        $validator = Validator::make($row->toArray(), [
            'sku' => 'required|string',
            'bodega' => 'required|string',
            'cantidad' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validación fallida: '.$validator->errors()->first());
        }

        // Find product
        $product = Product::where('sku', $row['sku'])
            ->where('company_id', $this->companyId)
            ->first();

        if (! $product) {
            throw new \Exception("Producto con SKU '{$row['sku']}' no encontrado");
        }

        // Find warehouse
        $warehouse = Warehouse::where('name', $row['bodega'])
            ->where('company_id', $this->companyId)
            ->first();

        if (! $warehouse) {
            throw new \Exception("Bodega '{$row['bodega']}' no encontrada");
        }

        $quantity = (float) $row['cantidad'];
        $unitCost = isset($row['costo_unitario']) ? (float) $row['costo_unitario'] : (isset($row['costo']) ? (float) $row['costo'] : $product->cost);

        // Create or update inventory
        $inventory = Inventory::updateOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'lot_number' => $row['numero_lote'] ?? $row['lote'] ?? null,
            ],
            [
                'quantity' => $quantity,
                'reserved_quantity' => $row['cantidad_reservada'] ?? $row['reservada'] ?? 0,
                'unit_cost' => $unitCost,
                'total_value' => $quantity * $unitCost,
                'location' => $row['ubicacion'] ?? null,
                'lot_number' => $row['numero_lote'] ?? $row['lote'] ?? null,
                'expiration_date' => isset($row['fecha_vencimiento']) ? \Carbon\Carbon::parse($row['fecha_vencimiento']) : null,
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]
        );

        // Create initial movement record
        InventoryMovement::create([
            'company_id' => $this->companyId,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'initial_stock',
            'quantity' => $quantity,
            'quantity_in' => $quantity,
            'quantity_out' => 0,
            'balance_quantity' => $quantity,
            'previous_quantity' => 0,
            'new_quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'movement_date' => now()->toDateString(),
            'reference_number' => 'IMP-'.now()->format('YmdHis'),
            'notes' => $row['notas'] ?? 'Importado desde Excel',
            'status' => 'completed',
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'completed_at' => now(),
            'is_active' => true,
            'active_at' => now(),
            'created_by' => $this->userId,
        ]);

        $this->successCount++;
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function chunkSize(): int
    {
        return 50;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getSummary(): array
    {
        return [
            'success' => $this->successCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->importErrors,
        ];
    }
}
