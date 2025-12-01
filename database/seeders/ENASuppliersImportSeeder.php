<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class ENASuppliersImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'like', '%Escuela Nacional de Agricultura%')->first();

        if (! $company) {
            $this->command->error('No se encontró la compañía ENA.');

            return;
        }

        // Eliminar proveedores existentes (soft delete)
        $deletedCount = Supplier::where('company_id', $company->id)->count();
        Supplier::where('company_id', $company->id)->delete();
        $this->command->info("Proveedores anteriores eliminados: {$deletedCount}");

        // Leer archivo Excel
        $filePath = base_path('plantilla_proveedores.xlsx');

        if (! file_exists($filePath)) {
            $this->command->error("Archivo no encontrado: {$filePath}");

            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $totalRows = $worksheet->getHighestRow();

        $this->command->info("Leyendo archivo Excel con {$totalRows} filas...");

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Iterar desde la fila 2 (saltando headers)
        for ($row = 2; $row <= $totalRows; $row++) {
            $name = $this->getCellValue($worksheet, 'A', $row);
            $taxId = $this->getCellValue($worksheet, 'C', $row);

            // Validar campos requeridos
            if (empty($name) || empty($taxId)) {
                $skippedCount++;
                $errors[] = "Fila {$row}: Nombre o NIT vacío";

                continue;
            }

            try {
                Supplier::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'legal_name' => $this->getCellValue($worksheet, 'B', $row),
                    'tax_id' => $taxId,
                    'email' => $this->getCellValue($worksheet, 'D', $row),
                    'phone' => $this->getCellValue($worksheet, 'E', $row),
                    'website' => $this->getCellValue($worksheet, 'F', $row),
                    'address' => $this->getCellValue($worksheet, 'G', $row),
                    'city' => $this->getCellValue($worksheet, 'H', $row),
                    'state' => $this->getCellValue($worksheet, 'I', $row),
                    'country' => $this->getCellValue($worksheet, 'J', $row) ?: 'El Salvador',
                    'postal_code' => $this->getCellValue($worksheet, 'K', $row),
                    'contact_person' => $this->getCellValue($worksheet, 'L', $row),
                    'contact_phone' => $this->getCellValue($worksheet, 'M', $row),
                    'contact_email' => $this->getCellValue($worksheet, 'N', $row),
                    'payment_terms' => $this->getCellValue($worksheet, 'O', $row),
                    'credit_limit' => $this->getNumericValue($worksheet, 'P', $row),
                    'rating' => $this->getNumericValue($worksheet, 'Q', $row),
                    'notes' => $this->getCellValue($worksheet, 'R', $row),
                    'is_active' => true,
                    'active_at' => now(),
                ]);

                $importedCount++;

                // Mostrar progreso cada 100 registros
                if ($importedCount % 100 === 0) {
                    $this->command->info("Procesados: {$importedCount} proveedores...");
                }
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Fila {$row}: ".$e->getMessage();
            }
        }

        $this->command->newLine();
        $this->command->info("Proveedores importados: {$importedCount}");

        if ($skippedCount > 0) {
            $this->command->warn("Proveedores omitidos: {$skippedCount}");

            // Mostrar primeros 10 errores
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->command->warn("  - {$error}");
            }

            if (count($errors) > 10) {
                $this->command->warn('  ... y '.(count($errors) - 10).' errores más');
            }
        }

        $this->command->info('Seeder ENASuppliersImportSeeder completado exitosamente.');
    }

    /**
     * Get cell value handling RichText objects.
     */
    private function getCellValue($worksheet, string $column, int $row): ?string
    {
        $cell = $worksheet->getCell($column.$row);
        $value = $cell->getValue();

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        if (is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * Get numeric cell value.
     */
    private function getNumericValue($worksheet, string $column, int $row): ?float
    {
        $value = $this->getCellValue($worksheet, $column, $row);

        if ($value === null) {
            return null;
        }

        // Remove any non-numeric characters except decimal point
        $value = preg_replace('/[^0-9.]/', '', $value);

        return is_numeric($value) ? (float) $value : null;
    }
}
