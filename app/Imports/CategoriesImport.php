<?php

namespace App\Imports;

use App\Models\ProductCategory;
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

class CategoriesImport implements SkipsOnError, SkipsOnFailure, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    protected int $companyId;

    protected int $userId;

    protected array $importErrors = [];

    protected int $successCount = 0;

    protected int $skippedCount = 0;

    protected int $updatedCount = 0;

    protected bool $previewMode = false;

    protected array $previewData = [];

    /** @var array<string, int> Cache of code => id for parent lookups */
    protected array $codeToIdMap = [];

    public function __construct(int $companyId, int $userId, bool $previewMode = false)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->previewMode = $previewMode;

        // Pre-load existing categories for this company
        $this->loadExistingCategories();
    }

    protected function loadExistingCategories(): void
    {
        $categories = ProductCategory::where('company_id', $this->companyId)
            ->whereNotNull('code')
            ->get(['id', 'code']);

        foreach ($categories as $category) {
            $this->codeToIdMap[$category->code] = $category->id;
        }
    }

    public function collection(Collection $rows): void
    {
        // First pass: create/update all categories without parent relationships
        // Second pass: update parent relationships
        $pendingParents = [];

        foreach ($rows as $index => $row) {
            try {
                if ($this->previewMode) {
                    $this->validateRow($row, $index + 2);
                } else {
                    $result = $this->processRow($row, $index + 2);
                    if ($result && ! empty($row['codigo_padre'])) {
                        $pendingParents[] = [
                            'code' => $row['codigo'],
                            'parent_code' => $row['codigo_padre'],
                        ];
                    }
                }
            } catch (\Exception $e) {
                $this->importErrors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray(),
                ];
                $this->skippedCount++;
            }
        }

        // Second pass: update parent relationships
        if (! $this->previewMode) {
            foreach ($pendingParents as $pending) {
                $this->updateParentRelationship($pending['code'], $pending['parent_code']);
            }
        }
    }

    protected function validateRow(Collection $row, int $rowNumber): void
    {
        $rowData = [
            'row' => $rowNumber,
            'codigo' => $row['codigo'] ?? null,
            'nombre' => $row['nombre'] ?? null,
            'codigo_padre' => $row['codigo_padre'] ?? null,
            'descripcion' => $row['descripcion'] ?? null,
            'status' => 'valid',
            'errors' => [],
            'warnings' => [],
            'action' => 'create',
        ];

        // Validate required fields
        $validator = Validator::make($row->toArray(), [
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
        ], [
            'codigo.required' => 'El código es requerido',
            'nombre.required' => 'El nombre es requerido',
        ]);

        if ($validator->fails()) {
            $rowData['status'] = 'error';
            $rowData['errors'] = $validator->errors()->all();
            $this->previewData[] = $rowData;
            $this->skippedCount++;

            return;
        }

        // Check if code already exists
        $existing = ProductCategory::where('code', $row['codigo'])
            ->where('company_id', $this->companyId)
            ->first();

        if ($existing) {
            $rowData['warnings'][] = "Código existente - se actualizará la categoría '{$existing->name}'";
            $rowData['action'] = 'update';
        }

        // Check parent code if provided
        if (! empty($row['codigo_padre'])) {
            $parentExists = ProductCategory::where('code', $row['codigo_padre'])
                ->where('company_id', $this->companyId)
                ->exists();

            // Also check if parent is in the current import (will be created)
            $parentInImport = collect($this->previewData)->contains(function ($item) use ($row) {
                return $item['codigo'] === $row['codigo_padre'];
            });

            if (! $parentExists && ! $parentInImport) {
                $rowData['warnings'][] = "Categoría padre '{$row['codigo_padre']}' no encontrada - asegúrese de que esté antes en el archivo";
            }
        }

        $rowData['status'] = count($rowData['errors']) > 0 ? 'error' : (count($rowData['warnings']) > 0 ? 'warning' : 'valid');
        $this->previewData[] = $rowData;

        if ($rowData['status'] !== 'error') {
            $this->successCount++;
        }
    }

    protected function processRow(Collection $row, int $rowNumber): ?ProductCategory
    {
        // Validate required fields
        $validator = Validator::make($row->toArray(), [
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validación fallida: '.$validator->errors()->first());
        }

        $code = trim($row['codigo']);
        $name = trim($row['nombre']);

        // Generate unique slug
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (ProductCategory::where('slug', $slug)
            ->where('company_id', $this->companyId)
            ->where('code', '!=', $code)
            ->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        // Check if category exists
        $existing = ProductCategory::where('code', $code)
            ->where('company_id', $this->companyId)
            ->first();

        if ($existing) {
            // Update existing
            $existing->update([
                'name' => $name,
                'slug' => $slug,
                'description' => $row['descripcion'] ?? $existing->description,
                'updated_by' => $this->userId,
            ]);

            $this->codeToIdMap[$code] = $existing->id;
            $this->updatedCount++;

            return $existing;
        }

        // Create new category (without parent_id for now)
        $category = ProductCategory::create([
            'company_id' => $this->companyId,
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'description' => $row['descripcion'] ?? null,
            'parent_id' => null, // Will be set in second pass
            'is_active' => true,
            'active_at' => now(),
            'created_by' => $this->userId,
        ]);

        $this->codeToIdMap[$code] = $category->id;
        $this->successCount++;

        return $category;
    }

    protected function updateParentRelationship(string $code, string $parentCode): void
    {
        $parentId = $this->codeToIdMap[$parentCode] ?? null;

        if (! $parentId) {
            // Try to find parent in database
            $parent = ProductCategory::where('code', $parentCode)
                ->where('company_id', $this->companyId)
                ->first();

            if ($parent) {
                $parentId = $parent->id;
                $this->codeToIdMap[$parentCode] = $parentId;
            }
        }

        if ($parentId) {
            $categoryId = $this->codeToIdMap[$code] ?? null;
            if ($categoryId) {
                ProductCategory::where('id', $categoryId)->update(['parent_id' => $parentId]);
            }
        }
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportErrors(): array
    {
        return $this->importErrors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getSummary(): array
    {
        return [
            'success' => $this->successCount + $this->updatedCount,
            'created' => $this->successCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->importErrors,
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
        $toCreate = collect($this->previewData)->where('action', 'create')->count();
        $toUpdate = collect($this->previewData)->where('action', 'update')->count();

        return [
            'total' => count($this->previewData),
            'valid' => $valid,
            'warnings' => $warnings,
            'errors' => $errors,
            'to_create' => $toCreate,
            'to_update' => $toUpdate,
            'can_import' => $errors === 0,
            'rows' => $this->previewData,
        ];
    }

    public function isPreviewMode(): bool
    {
        return $this->previewMode;
    }
}
