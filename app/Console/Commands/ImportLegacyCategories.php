<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyCategories extends Command
{
    protected $signature = 'categories:import-legacy
                            {file? : Ruta al archivo JSON (por defecto: database/data/legacy_categories.json)}
                            {--company=1 : ID de la empresa}
                            {--dry-run : Simular importación sin guardar}
                            {--update : Actualizar categorías existentes}';

    protected $description = 'Importa categorías desde archivo JSON usando legacy_code';

    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected array $errors = [];

    /** @var array<string, int> Cache de legacy_code => id */
    protected array $legacyCodeMap = [];

    public function handle(): int
    {
        $file = $this->argument('file') ?? database_path('data/legacy_categories.json');
        $companyId = (int) $this->option('company');
        $dryRun = $this->option('dry-run');
        $updateExisting = $this->option('update');

        if (! file_exists($file)) {
            $this->error("El archivo no existe: {$file}");

            return self::FAILURE;
        }

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  Importación de Categorías por Legacy Code                     ║');
        $this->info('╠════════════════════════════════════════════════════════════════╣');
        $this->info("║  Archivo: {$file}");
        $this->info("║  Empresa ID: {$companyId}");
        if ($dryRun) {
            $this->warn('║  MODO SIMULACIÓN - No se guardarán cambios                     ║');
        }
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Cargar categorías existentes por legacy_code
        $this->loadExistingCategories($companyId);

        try {
            $jsonContent = file_get_contents($file);
            $categories = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Error al parsear JSON: '.json_last_error_msg());

                return self::FAILURE;
            }

            $totalRows = count($categories);
            $this->info("Total de categorías a procesar: {$totalRows}");
            $this->newLine();

            if (! $dryRun) {
                DB::beginTransaction();
            }

            // Separar categorías padre e hijas
            $parentRows = [];
            $childRows = [];

            foreach ($categories as $index => $category) {
                $parentLegacyCode = $category['parent_legacy_code'] ?? null;
                $legacyCode = $category['legacy_code'] ?? null;
                $name = $category['name'] ?? null;

                if (empty($legacyCode) || empty($name)) {
                    $this->errors[] = 'Índice '.($index + 1).': legacy_code o nombre vacío';
                    $this->skipped++;

                    continue;
                }

                if (empty($parentLegacyCode)) {
                    $parentRows[] = ['row' => $index + 1, 'legacy_code' => $legacyCode, 'name' => $name];
                } else {
                    $childRows[] = ['row' => $index + 1, 'parent_legacy_code' => $parentLegacyCode, 'legacy_code' => $legacyCode, 'name' => $name];
                }
            }

            // Procesar categorías padre
            $this->info('Procesando categorías padre ('.count($parentRows).')...');
            foreach ($parentRows as $data) {
                $this->processCategory($data, $companyId, null, $dryRun, $updateExisting);
            }

            // Procesar subcategorías
            $this->newLine();
            $this->info('Procesando subcategorías ('.count($childRows).')...');
            foreach ($childRows as $data) {
                $parentId = $this->legacyCodeMap[$data['parent_legacy_code']] ?? null;

                if (! $parentId) {
                    $this->errors[] = "Índice {$data['row']}: Categoría padre con legacy_code '{$data['parent_legacy_code']}' no encontrada";
                    $this->skipped++;

                    continue;
                }

                $this->processCategory($data, $companyId, $parentId, $dryRun, $updateExisting);
            }

            if (! $dryRun) {
                DB::commit();
            }

            $this->newLine();
            $this->showSummary($dryRun);

            return self::SUCCESS;

        } catch (\Exception $e) {
            if (! $dryRun) {
                DB::rollBack();
            }

            $this->error('Error durante la importación: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function loadExistingCategories(int $companyId): void
    {
        $categories = ProductCategory::where('company_id', $companyId)
            ->whereNotNull('legacy_code')
            ->get(['id', 'legacy_code', 'name']);

        foreach ($categories as $category) {
            $this->legacyCodeMap[$category->legacy_code] = $category->id;
        }

        $this->info('Categorías existentes cargadas: '.count($this->legacyCodeMap));
    }

    protected function processCategory(array $data, int $companyId, ?int $parentId, bool $dryRun, bool $updateExisting): void
    {
        $legacyCode = $data['legacy_code'];
        $name = $data['name'];

        // Verificar si ya existe
        $existing = ProductCategory::where('legacy_code', $legacyCode)
            ->where('company_id', $companyId)
            ->first();

        if ($existing) {
            if ($updateExisting) {
                if (! $dryRun) {
                    $existing->update([
                        'name' => $name,
                        'parent_id' => $parentId,
                    ]);
                }
                $this->updated++;
                $this->line("  ↻ Actualizada: [{$legacyCode}] {$name}");
            } else {
                $this->skipped++;
                $this->line("  ○ Ya existe: [{$legacyCode}] {$name}");
            }
            $this->legacyCodeMap[$legacyCode] = $existing->id;

            return;
        }

        // Crear nueva categoría
        if ($dryRun) {
            // En modo simulación, usar ID temporal negativo para el mapa
            $this->legacyCodeMap[$legacyCode] = -1 * (int) $legacyCode;
        } else {
            // Sluggable se encarga de generar el slug automáticamente
            $category = ProductCategory::create([
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'name' => $name,
                'legacy_code' => $legacyCode,
                'code' => $this->generateCode($companyId),
                'is_active' => true,
                'active_at' => now(),
            ]);
            $this->legacyCodeMap[$legacyCode] = $category->id;
        }

        $this->created++;
        $parentInfo = $parentId ? " (hijo de {$data['parent_legacy_code']})" : ' (padre)';
        $this->line("  ✓ Creada: [{$legacyCode}] {$name}{$parentInfo}");
    }

    protected function generateCode(int $companyId): string
    {
        $lastCategory = ProductCategory::where('company_id', $companyId)
            ->whereNotNull('code')
            ->orderByRaw('CAST(code AS UNSIGNED) DESC')
            ->first();

        $nextNumber = $lastCategory ? ((int) $lastCategory->code + 1) : 1;

        return str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function showSummary(bool $dryRun): void
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  Resumen de Importación                                        ║');
        $this->info('╠════════════════════════════════════════════════════════════════╣');
        $this->info("║  Creadas: {$this->created}");
        $this->info("║  Actualizadas: {$this->updated}");
        $this->info("║  Omitidas: {$this->skipped}");
        $this->info('║  Errores: '.count($this->errors));

        if ($dryRun) {
            $this->warn('║  (SIMULACIÓN - No se guardaron cambios)                        ║');
        }

        $this->info('╚════════════════════════════════════════════════════════════════╝');

        if (count($this->errors) > 0) {
            $this->newLine();
            $this->warn('Errores encontrados:');
            foreach ($this->errors as $error) {
                $this->line("  • {$error}");
            }
        }
    }
}
