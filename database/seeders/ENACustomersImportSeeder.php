<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class ENACustomersImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Importa destinatarios internos (departamentos/usuarios) para despachos de bodega.
     * Estos NO son clientes de venta externa, sino unidades internas que reciben materiales.
     */
    public function run(): void
    {
        $company = Company::where('name', 'like', '%Escuela Nacional de Agricultura%')->first();

        if (! $company) {
            $this->command->error('No se encontró la compañía ENA.');

            return;
        }

        // Eliminar clientes/destinatarios existentes (force delete para evitar duplicados)
        $deletedCount = Customer::where('company_id', $company->id)->count();
        Customer::where('company_id', $company->id)->forceDelete();
        $this->command->info("Destinatarios anteriores eliminados: {$deletedCount}");

        // Leer archivo Excel
        $filePath = base_path('plantilla_clientes.xlsx');

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
            $code = $this->getCellValue($worksheet, 'A', $row);
            $name = $this->getCellValue($worksheet, 'B', $row);
            $type = $this->getCellValue($worksheet, 'C', $row);

            // Validar campos requeridos
            if (empty($code) || empty($name)) {
                $skippedCount++;
                $errors[] = "Fila {$row}: Código o Nombre vacío";

                continue;
            }

            try {
                Customer::create([
                    'company_id' => $company->id,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'email' => $this->getCellValue($worksheet, 'D', $row),
                    'phone' => $this->getCellValue($worksheet, 'E', $row),
                    'website' => $this->getCellValue($worksheet, 'F', $row),
                    'billing_address' => $this->getCellValue($worksheet, 'G', $row),
                    'billing_city' => $this->getCellValue($worksheet, 'H', $row),
                    'billing_state' => $this->getCellValue($worksheet, 'I', $row),
                    'billing_country' => $this->getCellValue($worksheet, 'J', $row),
                    'billing_postal_code' => $this->getCellValue($worksheet, 'K', $row),
                    'shipping_address' => $this->getCellValue($worksheet, 'L', $row),
                    'shipping_city' => $this->getCellValue($worksheet, 'M', $row),
                    'shipping_state' => $this->getCellValue($worksheet, 'N', $row),
                    'shipping_country' => $this->getCellValue($worksheet, 'O', $row),
                    'shipping_postal_code' => $this->getCellValue($worksheet, 'P', $row),
                    'same_as_billing' => $this->getBooleanValue($worksheet, 'Q', $row),
                    'contact_person' => $this->getCellValue($worksheet, 'R', $row),
                    'contact_phone' => $this->getCellValue($worksheet, 'S', $row),
                    'contact_email' => $this->getCellValue($worksheet, 'T', $row),
                    'contact_position' => $this->getCellValue($worksheet, 'U', $row),
                    'payment_terms' => $this->getCellValue($worksheet, 'V', $row),
                    'payment_method' => $this->getCellValue($worksheet, 'W', $row),
                    'currency' => $this->getCellValue($worksheet, 'X', $row),
                    'credit_limit' => $this->getNumericValue($worksheet, 'Y', $row),
                    'discount_percentage' => $this->getNumericValue($worksheet, 'Z', $row),
                    'notes' => $this->getCellValue($worksheet, 'AA', $row),
                    'is_active' => true,
                    'active_at' => now(),
                ]);

                $importedCount++;

                // Mostrar progreso cada 20 registros
                if ($importedCount % 20 === 0) {
                    $this->command->info("Procesados: {$importedCount} destinatarios...");
                }
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Fila {$row}: ".$e->getMessage();
            }
        }

        $this->command->newLine();
        $this->command->info("Destinatarios importados: {$importedCount}");

        if ($skippedCount > 0) {
            $this->command->warn("Destinatarios omitidos: {$skippedCount}");

            foreach (array_slice($errors, 0, 10) as $error) {
                $this->command->warn("  - {$error}");
            }

            if (count($errors) > 10) {
                $this->command->warn('  ... y '.(count($errors) - 10).' errores más');
            }
        }

        $this->command->info('Seeder ENACustomersImportSeeder completado exitosamente.');
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

        $value = preg_replace('/[^0-9.]/', '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Get boolean cell value from Si/No text.
     */
    private function getBooleanValue($worksheet, string $column, int $row): bool
    {
        $value = $this->getCellValue($worksheet, $column, $row);

        if ($value === null) {
            return false;
        }

        return strtolower(trim($value)) === 'si';
    }
}
