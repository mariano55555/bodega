<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

class InventoryBodegaGeneralImport implements SkipsOnError, SkipsOnFailure, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    protected int $companyId;

    protected int $userId;

    protected ?int $warehouseId;

    protected bool $previewMode;

    protected array $importErrors = [];

    protected int $successCount = 0;

    protected int $skippedCount = 0;

    protected array $previewData = [];

    protected array $categoriesNotFound = [];

    protected array $unitsToCreate = [];

    protected array $productsToCreate = [];

    protected int $currentRow = 1; // Start at 1 (header is row 1)

    /**
     * Unit normalization mappings.
     * Maps Excel values to existing unit names in the database.
     */
    protected array $unitMappings = [
        // Direct mappings to existing units
        'c/u' => 'unidad',
        'lb' => 'libra',
        'lbs' => 'libra',
        'galon' => 'galón',
        'ciento' => 'cientos',
        'millares' => 'millar',
        // Plurals to singular
        'bolsas' => 'bolsa',
        'cajas' => 'caja',
        'paquetes' => 'paquete',
        'rollos' => 'rollo',
        'pliegos' => 'pliego',
        'juegos' => 'juego',
        'frascos' => 'frasco',
        'cubos' => 'cubo',
        'cubetas' => 'cubeta',
        'blocks' => 'block',
        'yardas' => 'yarda',
        'metros' => 'metro',
        'litros' => 'litro',
        'docenas' => 'docena',
        'resmas' => 'resma',
        'pares' => 'par',
        // Special cases
        '500grs' => '500 gramos',
        '7612c/u' => 'unidad',
        'calidadjg' => 'unidad',
        'griscubeta' => 'cubeta',
    ];

    public function __construct(int $companyId, int $userId, ?int $warehouseId = null, bool $previewMode = false)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->warehouseId = $warehouseId;
        $this->previewMode = $previewMode;
    }

    public function collection(Collection $rows): void
    {
        if ($this->previewMode) {
            foreach ($rows as $row) {
                $this->currentRow++;
                try {
                    $this->validateRow($row, $this->currentRow);
                } catch (\Exception $e) {
                    $this->importErrors[] = [
                        'row' => $this->currentRow,
                        'error' => $e->getMessage(),
                        'data' => $row->toArray(),
                    ];
                    $this->skippedCount++;
                }
            }
        } else {
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    $this->currentRow++;
                    try {
                        $this->processRow($row, $this->currentRow);
                    } catch (\Exception $e) {
                        $this->importErrors[] = [
                            'row' => $this->currentRow,
                            'error' => $e->getMessage(),
                            'data' => $row->toArray(),
                        ];
                        $this->skippedCount++;
                    }
                }
            });
        }
    }

    protected function validateRow(Collection $row, int $rowNumber): void
    {
        $productName = $row['nombre_del_producto'] ?? null;
        $barcode = $row['codigo_de_barras'] ?? null;
        $warehouseCode = $row['bodega'] ?? null;
        $subcategoryCode = $row['subcategoria'] ?? null;
        $unitName = $row['unidad_de_medida'] ?? null;
        $quantity = $row['existencia_actual'] ?? null;
        $unitCost = $row['c_peps'] ?? $row['cpeps'] ?? null;

        // Skip rows that are Excel formulas (totals row)
        if ($this->isExcelFormula($quantity) || $this->isExcelFormula($unitCost)) {
            return;
        }

        // Convert to safe numeric values
        $quantity = $this->toSafeFloat($quantity);
        $unitCost = $this->toSafeFloat($unitCost);

        // Skip empty rows silently
        if (empty($productName) && empty($unitName) && $quantity === 0.0) {
            return;
        }

        $rowData = [
            'row' => $rowNumber,
            'nombre' => $productName,
            'codigo_barras' => $barcode,
            'bodega' => $warehouseCode,
            'subcategoria' => $subcategoryCode,
            'unidad_medida' => $unitName,
            'cantidad' => $quantity,
            'costo' => $unitCost,
            'status' => 'valid',
            'errors' => [],
            'warnings' => [],
            'mappings' => [],
        ];

        // Validate required fields
        $validator = Validator::make([
            'nombre' => $productName,
            'unidad_medida' => $unitName,
            'cantidad' => $quantity,
        ], [
            'nombre' => 'required|string|max:255',
            'unidad_medida' => 'required|string',
            'cantidad' => 'required|numeric|min:0',
        ], [
            'nombre.required' => 'El nombre del producto es requerido',
            'unidad_medida.required' => 'La unidad de medida es requerida',
            'cantidad.required' => 'La cantidad es requerida',
        ]);

        if ($validator->fails()) {
            $rowData['status'] = 'error';
            $rowData['errors'] = $validator->errors()->all();
            $this->previewData[] = $rowData;
            $this->skippedCount++;

            return;
        }

        // Check warehouse
        $warehouse = $this->findWarehouse($warehouseCode);
        if ($warehouse) {
            $rowData['mappings']['warehouse'] = ['id' => $warehouse->id, 'name' => $warehouse->name, 'action' => 'use_existing'];
        } elseif ($this->warehouseId) {
            $warehouse = Warehouse::find($this->warehouseId);
            $rowData['mappings']['warehouse'] = ['id' => $warehouse->id, 'name' => $warehouse->name, 'action' => 'use_selected'];
            $rowData['warnings'][] = "Bodega '{$warehouseCode}' no encontrada, se usará la bodega seleccionada";
        } else {
            $rowData['status'] = 'error';
            $rowData['errors'][] = "Bodega '{$warehouseCode}' no encontrada";
            $this->previewData[] = $rowData;
            $this->skippedCount++;

            return;
        }

        // Check if product exists by name
        $existingProduct = Product::where('company_id', $this->companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($productName))])
            ->first();

        if ($existingProduct) {
            $rowData['mappings']['product'] = [
                'id' => $existingProduct->id,
                'name' => $existingProduct->name,
                'sku' => $existingProduct->sku,
                'action' => 'update',
            ];
        } else {
            $rowData['mappings']['product'] = ['action' => 'create'];
            $this->productsToCreate[] = $productName;
        }

        // Check category by legacy_code
        $category = $this->findCategoryByLegacyCode($subcategoryCode);
        if ($category) {
            $rowData['mappings']['category'] = [
                'id' => $category->id,
                'name' => $category->name,
                'legacy_code' => $category->legacy_code,
                'action' => 'use_existing',
            ];
        } else {
            $rowData['warnings'][] = "Categoría con legacy_code '{$subcategoryCode}' no encontrada - se importará sin categoría";
            $rowData['mappings']['category'] = ['action' => 'not_found', 'legacy_code' => $subcategoryCode];
            $this->categoriesNotFound[$subcategoryCode] = ($this->categoriesNotFound[$subcategoryCode] ?? 0) + 1;
        }

        // Check unit of measure
        $normalizedUnit = $this->normalizeUnitName($unitName);
        $unit = $this->findOrCreateUnitPreview($normalizedUnit);
        if ($unit['exists']) {
            $rowData['mappings']['unit'] = [
                'id' => $unit['unit']->id,
                'name' => $unit['unit']->name,
                'abbreviation' => $unit['unit']->abbreviation,
                'action' => 'use_existing',
            ];
        } else {
            $rowData['warnings'][] = "Unidad '{$unitName}' normalizada a '{$normalizedUnit}' - se creará";
            $rowData['mappings']['unit'] = ['action' => 'create', 'normalized' => $normalizedUnit];
            $this->unitsToCreate[$normalizedUnit] = true;
        }

        $rowData['status'] = count($rowData['errors']) > 0 ? 'error' : (count($rowData['warnings']) > 0 ? 'warning' : 'valid');
        $this->previewData[] = $rowData;
        $this->successCount++;
    }

    protected function processRow(Collection $row, int $rowNumber): void
    {
        $productName = trim($row['nombre_del_producto'] ?? '');
        $barcode = $row['codigo_de_barras'] ?? null;
        $description = $row['descripcion'] ?? null;
        $warehouseCode = $row['bodega'] ?? null;
        $subcategoryCode = $row['subcategoria'] ?? null;
        $unitName = $row['unidad_de_medida'] ?? null;
        $minStock = $row['stock_minimo'] ?? null;
        $maxStock = $row['stock_maximo'] ?? null;

        // Get movement data from Excel (using safe conversion to handle formulas)
        $initialStock = $this->toSafeFloat($row['existencia_inicial'] ?? 0);
        $entries = $this->toSafeFloat($row['entradas'] ?? 0);
        $exits = $this->toSafeFloat($row['salidas'] ?? 0);
        $currentStock = $this->toSafeFloat($row['existencia_actual'] ?? 0);
        $unitCost = $this->toSafeFloat($row['c_peps'] ?? $row['cpeps'] ?? 0);

        // Skip rows that are Excel formulas (totals row)
        if ($this->isExcelFormula($row['existencia_actual'] ?? null) || $this->isExcelFormula($row['c_peps'] ?? $row['cpeps'] ?? null)) {
            return;
        }

        // Skip empty rows silently
        if (empty($productName) && empty($unitName) && $currentStock === 0.0) {
            return;
        }

        // Validate required fields
        if (empty($productName)) {
            throw new \Exception('El nombre del producto es requerido');
        }

        // Find warehouse
        $warehouse = $this->findWarehouse($warehouseCode);
        if (! $warehouse && $this->warehouseId) {
            $warehouse = Warehouse::find($this->warehouseId);
        }
        if (! $warehouse) {
            throw new \Exception("Bodega '{$warehouseCode}' no encontrada");
        }

        // Find category by legacy_code (nullable)
        $category = $this->findCategoryByLegacyCode($subcategoryCode);
        $categoryId = $category?->id;

        // Find or create unit of measure
        $normalizedUnit = $this->normalizeUnitName($unitName);
        $unit = $this->findOrCreateUnit($normalizedUnit);

        // Find or create product
        $product = Product::where('company_id', $this->companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower($productName)])
            ->first();

        if (! $product) {
            // Create product instance to generate SKU using category pattern
            $product = new Product([
                'company_id' => $this->companyId,
                'name' => $productName,
                'slug' => Str::slug($productName).'-'.Str::random(4),
                'description' => $description,
                'category_id' => $categoryId,
                'unit_of_measure_id' => $unit->id,
                'cost' => $unitCost,
                'barcode' => $barcode ?: null,
                'minimum_stock' => $minStock ?: 0,
                'maximum_stock' => $maxStock ?: null,
                'track_inventory' => true,
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $this->userId,
            ]);

            // Generate SKU using category-based pattern (parent_legacy-subcategory_legacy-correlative)
            $product->sku = Product::generateCategorySku($product);
            $product->save();
        } else {
            // Update existing product
            $product->update([
                'category_id' => $categoryId ?? $product->category_id,
                'unit_of_measure_id' => $unit->id,
                'cost' => $unitCost ?: $product->cost,
                'barcode' => $barcode ?: $product->barcode,
                'minimum_stock' => $minStock ?: $product->minimum_stock,
                'maximum_stock' => $maxStock ?: $product->maximum_stock,
                'updated_by' => $this->userId,
            ]);
        }

        // Create or update inventory with current stock
        $inventory = Inventory::updateOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => $currentStock,
                'reserved_quantity' => 0,
                'unit_cost' => $unitCost,
                'total_value' => $currentStock * $unitCost,
                'is_active' => true,
                'active_at' => now(),
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]
        );

        // Use product ID in reference to ensure uniqueness across re-imports
        $refPrefix = 'IMP-DIC25-P'.$product->id;
        $runningBalance = 0;

        // Check if we have detailed movement columns or just current stock
        $hasDetailedMovements = $initialStock > 0 || $entries > 0 || $exits > 0;

        if ($hasDetailedMovements) {
            // Movement 1: Initial stock (if > 0) - uses firstOrCreate to avoid duplicates
            if ($initialStock > 0) {
                $runningBalance = $initialStock;
                InventoryMovement::firstOrCreate(
                    [
                        'reference_number' => $refPrefix.'-INI',
                        'product_id' => $product->id,
                    ],
                    [
                        'company_id' => $this->companyId,
                        'warehouse_id' => $warehouse->id,
                        'movement_type' => 'adjustment',
                        'quantity' => $initialStock,
                        'quantity_in' => $initialStock,
                        'quantity_out' => 0,
                        'balance_quantity' => $runningBalance,
                        'previous_quantity' => 0,
                        'new_quantity' => $runningBalance,
                        'unit_cost' => $unitCost,
                        'total_cost' => $initialStock * $unitCost,
                        'movement_date' => '2025-12-01',
                        'notes' => 'Inventario inicial Diciembre 2025 - Carga desde Excel',
                        'status' => 'completed',
                        'is_confirmed' => true,
                        'confirmed_at' => now(),
                        'completed_at' => now(),
                        'is_active' => true,
                        'active_at' => now(),
                        'created_by' => $this->userId,
                    ]
                );
            }

            // Movement 2: Entries (if > 0) - uses adjustment type for initial load
            if ($entries > 0) {
                $previousQty = $runningBalance;
                $runningBalance += $entries;
                InventoryMovement::firstOrCreate(
                    [
                        'reference_number' => $refPrefix.'-ENT',
                        'product_id' => $product->id,
                    ],
                    [
                        'company_id' => $this->companyId,
                        'warehouse_id' => $warehouse->id,
                        'movement_type' => 'adjustment',
                        'quantity' => $entries,
                        'quantity_in' => $entries,
                        'quantity_out' => 0,
                        'balance_quantity' => $runningBalance,
                        'previous_quantity' => $previousQty,
                        'new_quantity' => $runningBalance,
                        'unit_cost' => $unitCost,
                        'total_cost' => $entries * $unitCost,
                        'movement_date' => '2025-12-15',
                        'notes' => 'Ajuste por entradas Diciembre 2025 - Carga desde Excel',
                        'status' => 'completed',
                        'is_confirmed' => true,
                        'confirmed_at' => now(),
                        'completed_at' => now(),
                        'is_active' => true,
                        'active_at' => now(),
                        'created_by' => $this->userId,
                    ]
                );
            }

            // Movement 3: Exits (if > 0) - uses adjustment type for initial load
            if ($exits > 0) {
                $previousQty = $runningBalance;
                $runningBalance -= $exits;
                InventoryMovement::firstOrCreate(
                    [
                        'reference_number' => $refPrefix.'-SAL',
                        'product_id' => $product->id,
                    ],
                    [
                        'company_id' => $this->companyId,
                        'warehouse_id' => $warehouse->id,
                        'movement_type' => 'adjustment',
                        'quantity' => $exits,
                        'quantity_in' => 0,
                        'quantity_out' => $exits,
                        'balance_quantity' => $runningBalance,
                        'previous_quantity' => $previousQty,
                        'new_quantity' => $runningBalance,
                        'unit_cost' => $unitCost,
                        'total_cost' => $exits * $unitCost,
                        'movement_date' => '2025-12-20',
                        'notes' => 'Ajuste por salidas Diciembre 2025 - Carga desde Excel',
                        'status' => 'completed',
                        'is_confirmed' => true,
                        'confirmed_at' => now(),
                        'completed_at' => now(),
                        'is_active' => true,
                        'active_at' => now(),
                        'created_by' => $this->userId,
                    ]
                );
            }
        } else {
            // No detailed movements - create a single adjustment with current stock
            if ($currentStock > 0) {
                InventoryMovement::firstOrCreate(
                    [
                        'reference_number' => $refPrefix.'-ADJ',
                        'product_id' => $product->id,
                    ],
                    [
                        'company_id' => $this->companyId,
                        'warehouse_id' => $warehouse->id,
                        'movement_type' => 'adjustment',
                        'quantity' => $currentStock,
                        'quantity_in' => $currentStock,
                        'quantity_out' => 0,
                        'balance_quantity' => $currentStock,
                        'previous_quantity' => 0,
                        'new_quantity' => $currentStock,
                        'unit_cost' => $unitCost,
                        'total_cost' => $currentStock * $unitCost,
                        'movement_date' => now()->toDateString(),
                        'notes' => 'Carga inicial de inventario desde Excel',
                        'status' => 'completed',
                        'is_confirmed' => true,
                        'confirmed_at' => now(),
                        'completed_at' => now(),
                        'is_active' => true,
                        'active_at' => now(),
                        'created_by' => $this->userId,
                    ]
                );
            }
        }

        $this->successCount++;
    }

    protected function findWarehouse(?string $code): ?Warehouse
    {
        if (empty($code)) {
            return null;
        }

        return Warehouse::where('company_id', $this->companyId)
            ->where(function ($q) use ($code) {
                $q->where('code', $code)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower(trim($code))]);
            })
            ->first();
    }

    protected function findCategoryByLegacyCode(?string $legacyCode): ?ProductCategory
    {
        if (empty($legacyCode)) {
            return null;
        }

        return ProductCategory::where('company_id', $this->companyId)
            ->where('legacy_code', $legacyCode)
            ->first();
    }

    protected function normalizeUnitName(?string $name): string
    {
        if (empty($name)) {
            return 'unidad';
        }

        $normalized = strtolower(trim($name));

        // Check direct mapping first (for simple mappings like c/u -> unidad)
        if (isset($this->unitMappings[$normalized])) {
            return $this->unitMappings[$normalized];
        }

        // Keep compound units like "bolsa 25", "sobre 100", "caja 12" as-is
        // These represent specific package sizes and should be preserved
        return $normalized;
    }

    /**
     * Generate a unique abbreviation for a unit name.
     * For compound units like "bolsa 25", generates "BLS25" instead of just "BOLSA".
     */
    protected function generateUnitAbbreviation(string $normalizedName): string
    {
        // Check for compound patterns like "bolsa 25", "sobre 100", "caja 12"
        if (preg_match('/^([a-záéíóúñ]+)\s*(\d+)/i', $normalizedName, $matches)) {
            $baseUnit = strtolower($matches[1]);
            $number = $matches[2];

            // Common abbreviations for base units
            $baseAbbreviations = [
                'bolsa' => 'BLS',
                'sobre' => 'SBR',
                'caja' => 'CJA',
                'paquete' => 'PQT',
                'frasco' => 'FRS',
                'botella' => 'BTL',
                'galón' => 'GAL',
                'galon' => 'GAL',
                'litro' => 'LTR',
                'kilo' => 'KG',
                'libra' => 'LB',
                'unidad' => 'UND',
                'metro' => 'MTR',
                'rollo' => 'RLL',
                'cubeta' => 'CBT',
            ];

            $baseAbbr = $baseAbbreviations[$baseUnit] ?? strtoupper(substr($baseUnit, 0, 3));

            return $baseAbbr.$number;
        }

        // For simple units, use first 5 characters uppercase
        return strtoupper(substr($normalizedName, 0, 5));
    }

    protected function findOrCreateUnitPreview(string $normalizedName): array
    {
        // Search for exact name match only (case-insensitive)
        $unit = UnitOfMeasure::where(function ($q) {
            $q->where('company_id', $this->companyId)
                ->orWhereNull('company_id');
        })
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->first();

        $abbreviation = $this->generateUnitAbbreviation($normalizedName);

        return ['exists' => (bool) $unit, 'unit' => $unit, 'abbreviation' => $abbreviation];
    }

    protected function findOrCreateUnit(string $normalizedName): UnitOfMeasure
    {
        // normalizedName is already lowercase from normalizeUnitName()
        // First try to find existing unit by exact name match (case-insensitive)
        $unit = UnitOfMeasure::where(function ($q) {
            $q->where('company_id', $this->companyId)
                ->orWhereNull('company_id');
        })
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->first();

        if ($unit) {
            return $unit;
        }

        // No exact match found - create new unit with unique abbreviation
        $abbreviation = $this->generateUnitAbbreviation($normalizedName);

        // Use firstOrCreate to avoid race conditions and duplicate key errors
        return UnitOfMeasure::firstOrCreate(
            ['abbreviation' => $abbreviation],
            [
                'company_id' => $this->companyId,
                'name' => $normalizedName, // Keep lowercase
                'slug' => Str::slug($normalizedName).'-'.Str::random(4),
                'active_at' => now(),
                'created_by' => $this->userId,
            ]
        );
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

    public function getPreviewData(): array
    {
        return $this->previewData;
    }

    public function getCategoriesNotFound(): array
    {
        return $this->categoriesNotFound;
    }

    public function getPreviewSummary(): array
    {
        $valid = collect($this->previewData)->where('status', 'valid')->count();
        $warnings = collect($this->previewData)->where('status', 'warning')->count();
        $errors = collect($this->previewData)->where('status', 'error')->count();

        // Count products to create vs update
        $productsToCreate = collect($this->previewData)
            ->filter(fn ($row) => ($row['mappings']['product']['action'] ?? '') === 'create')
            ->count();

        $productsToUpdate = collect($this->previewData)
            ->filter(fn ($row) => ($row['mappings']['product']['action'] ?? '') === 'update')
            ->count();

        return [
            'total' => count($this->previewData),
            'valid' => $valid,
            'warnings' => $warnings,
            'errors' => $errors,
            'can_import' => $errors === 0,
            'products_to_create' => $productsToCreate,
            'products_to_update' => $productsToUpdate,
            'units_to_create' => array_keys($this->unitsToCreate),
            'categories_not_found' => $this->categoriesNotFound,
            'rows' => $this->previewData,
        ];
    }

    public function isPreviewMode(): bool
    {
        return $this->previewMode;
    }

    /**
     * Check if a value is an Excel formula (starts with =).
     */
    protected function isExcelFormula(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return str_starts_with(trim($value), '=');
    }

    /**
     * Convert a value to float safely, handling strings, nulls, and invalid values.
     */
    protected function toSafeFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // If it's a string that looks like a formula, return 0
        if (is_string($value) && str_starts_with(trim($value), '=')) {
            return 0.0;
        }

        // Try to extract numeric value from string
        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        return 0.0;
    }
}
