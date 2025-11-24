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

    protected array $errors = [];

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
                    $this->errors[] = [
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
        $cost = isset($row['costo']) ? (float) $row['costo'] : $product->cost;

        // Create or update inventory
        $inventory = Inventory::updateOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => $quantity,
                'reserved_quantity' => $row['reservada'] ?? 0,
                'minimum_stock' => $row['stock_minimo'] ?? $product->minimum_stock,
                'maximum_stock' => $row['stock_maximo'] ?? $product->maximum_stock,
                'cost' => $cost,
                'total_value' => $quantity * $cost,
                'location' => $row['ubicacion'] ?? null,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]
        );

        // Create initial movement record
        InventoryMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'initial_import',
            'quantity' => $quantity,
            'cost' => $cost,
            'total_value' => $quantity * $cost,
            'balance_before' => 0,
            'balance_after' => $quantity,
            'movement_date' => now(),
            'reference' => 'Importación inicial de inventarios',
            'notes' => isset($row['notas']) ? $row['notas'] : 'Importado desde Excel',
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
        return $this->errors;
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
            'errors' => $this->errors,
        ];
    }
}
