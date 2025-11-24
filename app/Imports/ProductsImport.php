<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements SkipsOnError, SkipsOnFailure, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    protected int $companyId;

    protected int $userId;

    protected array $errors = [];

    protected int $successCount = 0;

    protected int $skippedCount = 0;

    protected bool $previewMode = false;

    protected array $previewData = [];

    protected array $warnings = [];

    public function __construct(int $companyId, int $userId, bool $previewMode = false)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->previewMode = $previewMode;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                if ($this->previewMode) {
                    $this->validateRow($row, $index + 2);
                } else {
                    $this->processRow($row, $index + 2); // +2 because of header row and 0-based index
                }
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray(),
                ];
                $this->skippedCount++;
            }
        }
    }

    protected function validateRow(Collection $row, int $rowNumber): void
    {
        $rowData = [
            'row' => $rowNumber,
            'sku' => $row['sku'] ?? null,
            'nombre' => $row['nombre'] ?? null,
            'categoria' => $row['categoria'] ?? null,
            'unidad_medida' => $row['unidad_medida'] ?? null,
            'status' => 'valid',
            'errors' => [],
            'warnings' => [],
            'mappings' => [],
        ];

        // Validate required fields
        $validator = Validator::make($row->toArray(), [
            'sku' => 'required|string|max:100',
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string',
            'unidad_medida' => 'required|string',
        ], [
            'sku.required' => 'El SKU es requerido',
            'nombre.required' => 'El nombre es requerido',
            'categoria.required' => 'La categoría es requerida',
            'unidad_medida.required' => 'La unidad de medida es requerida',
        ]);

        if ($validator->fails()) {
            $rowData['status'] = 'error';
            $rowData['errors'] = $validator->errors()->all();
            $this->previewData[] = $rowData;
            $this->skippedCount++;

            return;
        }

        // Check if SKU already exists
        $existingProduct = Product::where('sku', $row['sku'])
            ->where('company_id', $this->companyId)
            ->first();

        if ($existingProduct) {
            $rowData['warnings'][] = "SKU existente - se actualizará el producto '{$existingProduct->name}'";
            $rowData['mappings']['product'] = ['id' => $existingProduct->id, 'name' => $existingProduct->name, 'action' => 'update'];
        } else {
            $rowData['mappings']['product'] = ['action' => 'create'];
        }

        // Check category - find exact or similar match
        $categoryMatch = $this->findCategoryMatch($row['categoria']);
        if ($categoryMatch['exact']) {
            $rowData['mappings']['category'] = ['id' => $categoryMatch['category']->id, 'name' => $categoryMatch['category']->name, 'action' => 'use_existing'];
        } elseif ($categoryMatch['similar']) {
            $rowData['warnings'][] = "Categoría '{$row['categoria']}' no encontrada exacta, pero existe '{$categoryMatch['similar']->name}'. Se creará nueva.";
            $rowData['mappings']['category'] = ['action' => 'create', 'similar' => $categoryMatch['similar']->name];
        } else {
            $rowData['warnings'][] = "Categoría '{$row['categoria']}' no existe - se creará automáticamente";
            $rowData['mappings']['category'] = ['action' => 'create'];
        }

        // Check unit of measure - find exact or similar match
        $unitMatch = $this->findUnitMatch($row['unidad_medida']);
        if ($unitMatch['exact']) {
            $rowData['mappings']['unit'] = ['id' => $unitMatch['unit']->id, 'name' => $unitMatch['unit']->name, 'symbol' => $unitMatch['unit']->symbol, 'action' => 'use_existing'];
        } elseif ($unitMatch['similar']) {
            $rowData['warnings'][] = "Unidad '{$row['unidad_medida']}' similar a '{$unitMatch['similar']->name}' ({$unitMatch['similar']->symbol}). Se creará nueva si no coincide exactamente.";
            $rowData['mappings']['unit'] = ['action' => 'create', 'similar' => $unitMatch['similar']->name];
        } else {
            $rowData['warnings'][] = "Unidad de medida '{$row['unidad_medida']}' no existe - se creará automáticamente";
            $rowData['mappings']['unit'] = ['action' => 'create'];
        }

        // Check supplier if provided
        if (! empty($row['proveedor'])) {
            $supplier = Supplier::where('company_id', $this->companyId)
                ->where('name', $row['proveedor'])
                ->first();

            if ($supplier) {
                $rowData['mappings']['supplier'] = ['id' => $supplier->id, 'name' => $supplier->name, 'action' => 'use_existing'];
            } else {
                $rowData['warnings'][] = "Proveedor '{$row['proveedor']}' no encontrado - se ignorará";
                $rowData['mappings']['supplier'] = ['action' => 'ignore'];
            }
        }

        $rowData['status'] = count($rowData['errors']) > 0 ? 'error' : (count($rowData['warnings']) > 0 ? 'warning' : 'valid');
        $this->previewData[] = $rowData;
        $this->successCount++;
    }

    protected function findCategoryMatch(string $name): array
    {
        // Try exact match first (case-insensitive)
        $exact = Category::where('company_id', $this->companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->first();

        if ($exact) {
            return ['exact' => $exact, 'similar' => null, 'category' => $exact];
        }

        // Try similar match using LIKE
        $similar = Category::where('company_id', $this->companyId)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($name)) . '%'])
            ->first();

        return ['exact' => null, 'similar' => $similar, 'category' => null];
    }

    protected function findUnitMatch(string $name): array
    {
        $normalizedName = strtolower(trim($name));

        // Try exact match by name (case-insensitive)
        $exact = UnitOfMeasure::where('company_id', $this->companyId)
            ->where(function ($q) use ($normalizedName) {
                $q->whereRaw('LOWER(name) = ?', [$normalizedName])
                    ->orWhereRaw('LOWER(symbol) = ?', [$normalizedName]);
            })
            ->first();

        if ($exact) {
            return ['exact' => $exact, 'similar' => null, 'unit' => $exact];
        }

        // Try similar match - using common abbreviations and plurals
        $variations = $this->getUnitVariations($normalizedName);

        foreach ($variations as $variation) {
            $similar = UnitOfMeasure::where('company_id', $this->companyId)
                ->where(function ($q) use ($variation) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . $variation . '%'])
                        ->orWhereRaw('LOWER(symbol) LIKE ?', ['%' . $variation . '%']);
                })
                ->first();

            if ($similar) {
                return ['exact' => null, 'similar' => $similar, 'unit' => null];
            }
        }

        return ['exact' => null, 'similar' => null, 'unit' => null];
    }

    protected function getUnitVariations(string $name): array
    {
        $variations = [$name];

        // Common unit mappings (Spanish)
        $mappings = [
            'kilogramo' => ['kg', 'kilo', 'kilos', 'kilogramos'],
            'kg' => ['kilogramo', 'kilo', 'kilos', 'kilogramos'],
            'gramo' => ['g', 'gr', 'gramos'],
            'g' => ['gramo', 'gr', 'gramos'],
            'litro' => ['l', 'lt', 'lts', 'litros'],
            'l' => ['litro', 'lt', 'lts', 'litros'],
            'mililitro' => ['ml', 'mililitros'],
            'ml' => ['mililitro', 'mililitros'],
            'unidad' => ['u', 'und', 'unidades', 'pza', 'pieza', 'piezas'],
            'und' => ['u', 'unidad', 'unidades', 'pza', 'pieza', 'piezas'],
            'pieza' => ['pza', 'piezas', 'u', 'und', 'unidad'],
            'caja' => ['cajas', 'cj'],
            'docena' => ['doc', 'docenas'],
            'metro' => ['m', 'mts', 'metros'],
            'm' => ['metro', 'mts', 'metros'],
            'centimetro' => ['cm', 'centimetros'],
            'cm' => ['centimetro', 'centimetros'],
            'libra' => ['lb', 'lbs', 'libras'],
            'lb' => ['libra', 'lbs', 'libras'],
            'onza' => ['oz', 'onzas'],
            'oz' => ['onza', 'onzas'],
            'galon' => ['gal', 'galones'],
            'gal' => ['galon', 'galones'],
            'quintal' => ['qq', 'quintales'],
            'qq' => ['quintal', 'quintales'],
            'saco' => ['sacos'],
            'bolsa' => ['bolsas'],
            'paquete' => ['paq', 'paquetes'],
        ];

        if (isset($mappings[$name])) {
            $variations = array_merge($variations, $mappings[$name]);
        }

        // Also try without accents
        $withoutAccents = $this->removeAccents($name);
        if ($withoutAccents !== $name) {
            $variations[] = $withoutAccents;
        }

        return array_unique($variations);
    }

    protected function removeAccents(string $string): string
    {
        return strtr($string, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N',
        ]);
    }

    protected function processRow(Collection $row, int $rowNumber): void
    {
        // Validate required fields
        $validator = Validator::make($row->toArray(), [
            'sku' => 'required|string|max:100',
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string',
            'unidad_medida' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validación fallida: '.$validator->errors()->first());
        }

        // Find or create category
        $category = Category::firstOrCreate(
            [
                'name' => $row['categoria'],
                'company_id' => $this->companyId,
            ],
            [
                'slug' => Str::slug($row['categoria']),
                'description' => 'Importado automáticamente',
                'active_at' => now(),
                'created_by' => $this->userId,
            ]
        );

        // Find or create unit of measure
        $unitOfMeasure = UnitOfMeasure::firstOrCreate(
            [
                'name' => $row['unidad_medida'],
                'company_id' => $this->companyId,
            ],
            [
                'slug' => Str::slug($row['unidad_medida']),
                'abbreviation' => substr($row['unidad_medida'], 0, 10),
                'active_at' => now(),
                'created_by' => $this->userId,
            ]
        );

        // Find supplier if provided
        $supplierId = null;
        if (! empty($row['proveedor'])) {
            $supplier = Supplier::where('company_id', $this->companyId)
                ->where('name', $row['proveedor'])
                ->first();

            if ($supplier) {
                $supplierId = $supplier->id;
            }
        }

        // Create or update product
        $product = Product::updateOrCreate(
            [
                'sku' => $row['sku'],
                'company_id' => $this->companyId,
            ],
            [
                'name' => $row['nombre'],
                'slug' => Str::slug($row['nombre'].'-'.$row['sku']),
                'description' => $row['descripcion'] ?? null,
                'category_id' => $category->id,
                'unit_of_measure_id' => $unitOfMeasure->id,
                'supplier_id' => $supplierId,
                'cost' => $row['costo'] ?? 0,
                'price' => $row['precio'] ?? 0,
                'minimum_stock' => $row['stock_minimo'] ?? 0,
                'maximum_stock' => $row['stock_maximo'] ?? null,
                'reorder_point' => $row['punto_reorden'] ?? null,
                'barcode' => $row['codigo_barras'] ?? null,
                'internal_code' => $row['codigo_interno'] ?? null,
                'brand' => $row['marca'] ?? null,
                'model' => $row['modelo'] ?? null,
                'is_perishable' => ! empty($row['perecedero']) && in_array(strtolower($row['perecedero']), ['si', 'yes', '1', 'true']),
                'shelf_life_days' => $row['vida_util_dias'] ?? null,
                'requires_serial' => ! empty($row['requiere_serie']) && in_array(strtolower($row['requiere_serie']), ['si', 'yes', '1', 'true']),
                'requires_lot' => ! empty($row['requiere_lote']) && in_array(strtolower($row['requiere_lote']), ['si', 'yes', '1', 'true']),
                'active_at' => now(),
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]
        );

        $this->successCount++;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
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

    public function getPreviewData(): array
    {
        return $this->previewData;
    }

    public function getPreviewSummary(): array
    {
        $valid = collect($this->previewData)->where('status', 'valid')->count();
        $warnings = collect($this->previewData)->where('status', 'warning')->count();
        $errors = collect($this->previewData)->where('status', 'error')->count();

        // Collect all unique units that will be created
        $unitsToCreate = collect($this->previewData)
            ->pluck('unidad_medida')
            ->filter()
            ->map(fn($u) => strtolower(trim($u)))
            ->unique()
            ->filter(function ($unit) {
                $match = $this->findUnitMatch($unit);
                return ! $match['exact'];
            })
            ->values()
            ->toArray();

        // Collect all unique categories that will be created
        $categoriesToCreate = collect($this->previewData)
            ->pluck('categoria')
            ->filter()
            ->map(fn($c) => strtolower(trim($c)))
            ->unique()
            ->filter(function ($cat) {
                $match = $this->findCategoryMatch($cat);
                return ! $match['exact'];
            })
            ->values()
            ->toArray();

        return [
            'total' => count($this->previewData),
            'valid' => $valid,
            'warnings' => $warnings,
            'errors' => $errors,
            'can_import' => $errors === 0,
            'units_to_create' => $unitsToCreate,
            'categories_to_create' => $categoriesToCreate,
            'rows' => $this->previewData,
        ];
    }

    public function isPreviewMode(): bool
    {
        return $this->previewMode;
    }
}
