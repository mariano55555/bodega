<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ENACategoriesImportSeeder extends Seeder
{
    /**
     * Log file for categories without parent.
     */
    protected string $logFile = 'categories_import_errors.log';

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

        // Clear previous log
        $logPath = storage_path('logs/'.$this->logFile);
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        // First, create or update parent categories that may not exist
        $this->createOrUpdateParentCategories($company->id);

        $categories = $this->getCategories();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($categories as $child) {
            $legacyCode = $child['code'] ?? null;
            $name = $child['name'] ?? null;
            $parentCode = $child['parent_code'] ?? null;

            // Skip if parent_code is 543 (we don't have this parent)
            if ($parentCode === '543') {
                $this->command->warn("Saltando categoría {$legacyCode} ({$name}) - parent_code 543 no existe.");
                $this->logError("SKIPPED: Categoría {$legacyCode} ({$name}) - parent_code 543 no existe en el sistema.");
                $skipped++;

                continue;
            }

            if (! $legacyCode || ! $name) {
                $this->command->warn('Registro inválido sin código o nombre.');
                $skipped++;

                continue;
            }

            // Find parent by legacy_code
            $parentId = null;
            if ($parentCode) {
                $parent = ProductCategory::where('company_id', $company->id)
                    ->where('legacy_code', $parentCode)
                    ->first();

                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    $errorMsg = "Categoría padre no encontrada: legacy_code={$parentCode} para categoría {$legacyCode} ({$name})";
                    $this->command->warn($errorMsg);
                    $this->logError($errorMsg);
                    $errors[] = $legacyCode;
                    // Continue without parent_id
                }
            }

            // Check if category already exists
            $existingCategory = ProductCategory::where('company_id', $company->id)
                ->where('legacy_code', $legacyCode)
                ->first();

            $slug = Str::slug($name);

            // Check for slug uniqueness and make it unique if necessary
            $originalSlug = $slug;
            $counter = 1;
            while (ProductCategory::where('slug', $slug)
                ->where('id', '!=', $existingCategory?->id ?? 0)
                ->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }

            if ($existingCategory) {
                // Update existing category
                $existingCategory->update([
                    'name' => $name,
                    'slug' => $slug,
                    'parent_id' => $parentId,
                ]);
                $updated++;
                $this->command->info("Actualizado: {$legacyCode} - {$name}");
            } else {
                // Create new category
                // Generate a unique code
                $code = 'CAT-'.$legacyCode;

                // Check code uniqueness
                $originalCode = $code;
                $codeCounter = 1;
                while (ProductCategory::where('code', $code)->exists()) {
                    $code = $originalCode.'-'.$codeCounter;
                    $codeCounter++;
                }

                ProductCategory::create([
                    'company_id' => $company->id,
                    'parent_id' => $parentId,
                    'name' => $name,
                    'slug' => $slug,
                    'code' => $code,
                    'legacy_code' => $legacyCode,
                    'is_active' => true,
                    'active_at' => now(),
                ]);
                $created++;
                $this->command->info("Creado: {$legacyCode} - {$name}");
            }
        }

        $this->command->newLine();
        $this->command->info('=== Resumen de Importación ===');
        $this->command->info("Categorías creadas: {$created}");
        $this->command->info("Categorías actualizadas: {$updated}");
        $this->command->info("Categorías saltadas: {$skipped}");

        if (count($errors) > 0) {
            $this->command->warn('Categorías con errores de padre: '.count($errors));
            $this->command->warn("Revisar archivo de log: storage/logs/{$this->logFile}");
        }

        // Soft delete obsolete categories
        $deleted = $this->softDeleteObsoleteCategories($company->id);

        $this->command->newLine();
        $this->command->info("Categorías obsoletas eliminadas (soft delete): {$deleted}");
        $this->command->newLine();
        $this->command->info('Seeder ENACategoriesImportSeeder completado.');
    }

    /**
     * Create or update parent categories that may not exist.
     */
    protected function createOrUpdateParentCategories(int $companyId): void
    {
        $parentCategories = [
            ['code' => 'CAT-79', 'legacy_code' => '79', 'name' => 'Fondos Injuve', 'slug' => 'fondos-injuve-cat'],
        ];

        foreach ($parentCategories as $data) {
            $existing = ProductCategory::where('company_id', $companyId)
                ->where('legacy_code', $data['legacy_code'])
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'code' => $data['code'],
                ]);
                $this->command->info("Categoría padre actualizada: {$data['legacy_code']} - {$data['name']}");
            } else {
                ProductCategory::create([
                    'company_id' => $companyId,
                    'parent_id' => null,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'code' => $data['code'],
                    'legacy_code' => $data['legacy_code'],
                    'is_active' => true,
                    'active_at' => now(),
                ]);
                $this->command->info("Categoría padre creada: {$data['legacy_code']} - {$data['name']}");
            }
        }
    }

    /**
     * Log error to file.
     */
    protected function logError(string $message): void
    {
        $logPath = storage_path('logs/'.$this->logFile);
        $timestamp = now()->format('Y-m-d H:i:s');
        file_put_contents($logPath, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }

    /**
     * Soft delete obsolete categories that exist in DB but not in the seeder.
     */
    protected function softDeleteObsoleteCategories(int $companyId): int
    {
        $validLegacyCodes = array_column($this->getCategories(), 'code');

        $obsoleteLegacyCodes = [
            '54117', '61105', '61106', '61109',
            '70101', '70102', '70103', '70199',
            '71199',
            '72101', '72199',
            '73101', '73102', '73199',
            '74101', '74199',
            '75101', '75102', '75199',
            '76101', '76199',
            '77101', '77102', '77199',
            '78101', '78199',
            '80101', '80102', '80103', '80199',
        ];

        $deleted = 0;

        foreach ($obsoleteLegacyCodes as $legacyCode) {
            $category = ProductCategory::where('company_id', $companyId)
                ->where('legacy_code', $legacyCode)
                ->whereNotNull('parent_id')
                ->first();

            if ($category) {
                $category->delete();
                $this->command->warn("Eliminada (soft delete): {$legacyCode} - {$category->name}");
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get categories data.
     *
     * @return array<int, array{code: string, name: string, parent_code: string}>
     */
    protected function getCategories(): array
    {
        return [
            ['code' => '54101', 'name' => 'PRODUCTO ALIMENTICIO / PERSONA', 'parent_code' => '54'],
            ['code' => '54102', 'name' => 'PRODUC. ALIMEN. PARA ANIMALES', 'parent_code' => '54'],
            ['code' => '54103', 'name' => 'PRODUCT AGROPECUAR Y FOREST.', 'parent_code' => '54'],
            ['code' => '54104', 'name' => 'PRODUCTOS TEXTILES Y VESTUARIO', 'parent_code' => '54'],
            ['code' => '54105', 'name' => 'PRODUCTOS DE PAPEL Y CARTON', 'parent_code' => '54'],
            ['code' => '54106', 'name' => 'PRODUCTOS DE CUERO Y CAUCHO', 'parent_code' => '54'],
            ['code' => '54107', 'name' => 'PRODUCTOS QUIMICOS', 'parent_code' => '54'],
            ['code' => '54108', 'name' => 'PRODUCTOS FARMECEUTICOS Y MEDI', 'parent_code' => '54'],
            ['code' => '54109', 'name' => 'LLANTAS Y NEUMATICOS', 'parent_code' => '54'],
            ['code' => '54110', 'name' => 'COMBUSTIBLES Y LUBRICANTES', 'parent_code' => '54'],
            ['code' => '54111', 'name' => 'MINERALES NO METALICOS Y PRODU', 'parent_code' => '54'],
            ['code' => '54112', 'name' => 'MINERALES METALICOS Y PRODUCTO', 'parent_code' => '54'],
            ['code' => '54113', 'name' => 'MATERIALES E INSTRUMENTOS DE L', 'parent_code' => '54'],
            ['code' => '54114', 'name' => 'MATERIALES DE OFICINA', 'parent_code' => '54'],
            ['code' => '54115', 'name' => 'MATERIALES INFORMATICOS', 'parent_code' => '54'],
            ['code' => '54116', 'name' => 'LIBROS, TEXTOS,UTILES DE ENSEÑ', 'parent_code' => '54'],
            ['code' => '54118', 'name' => 'HERRAMIENTAS, REPUESTOS Y ACCE', 'parent_code' => '54'],
            ['code' => '54119', 'name' => 'MATERIALES ELECTRICOS', 'parent_code' => '54'],
            ['code' => '54199', 'name' => 'PRODUCT.DE USO Y CONS.DIVERSO', 'parent_code' => '54'],
            ['code' => '54313', 'name' => 'IMPRESIONES, PUBLICACIONES Y R', 'parent_code' => '54'],
            ['code' => '61101', 'name' => 'MOBILIARIOS', 'parent_code' => '61'],
            ['code' => '61102', 'name' => 'MAQUINARIA Y EQUIPO', 'parent_code' => '61'],
            ['code' => '61103', 'name' => 'EQUIPOS MEDICOS Y LABORATORIO', 'parent_code' => '61'],
            ['code' => '61104', 'name' => 'EQUIPOS INFORMATICOS', 'parent_code' => '61'],
            ['code' => '61107', 'name' => 'LIBROS Y COLECCIONES', 'parent_code' => '61'],
            ['code' => '61108', 'name' => 'HERRAMIENTAS Y REPUESTOS PRINC', 'parent_code' => '61'],
            ['code' => '61199', 'name' => 'BIENES MUEBLES DIVERSOS', 'parent_code' => '61'],
            ['code' => '61301', 'name' => 'GANADO VACUNO', 'parent_code' => '61'],
            ['code' => '61303', 'name' => 'GANADO PORCINO', 'parent_code' => '61'],
            ['code' => '61606', 'name' => 'ELECTRICAS Y COMUNICACIONES', 'parent_code' => '61'],
            ['code' => '68821', 'name' => '6882-61101', 'parent_code' => '75'],
            ['code' => '68822', 'name' => '6882-61102', 'parent_code' => '75'],
            ['code' => '68824', 'name' => '6882-61104', 'parent_code' => '75'],
            ['code' => '70000', 'name' => 'PROD. INTERNA (CONCENTRADOS)', 'parent_code' => '70'],
            ['code' => '70001', 'name' => 'PROD.INT.-HUEVOS', 'parent_code' => '70'],
            ['code' => '70002', 'name' => 'PROD.INT.-LACTEOS', 'parent_code' => '70'],
            ['code' => '70003', 'name' => 'PROD.INT.-POLLOS', 'parent_code' => '70'],
            ['code' => '70004', 'name' => 'PROD.INT.-CERDOS', 'parent_code' => '70'],
            ['code' => '70005', 'name' => 'PROD.INT.-OTROS PRODUCTOS', 'parent_code' => '70'],
            ['code' => '70006', 'name' => 'PROD. INT. HORTALIZAS E INVERN', 'parent_code' => '70'],
            ['code' => '70007', 'name' => 'PROD. INT. FRUTALES', 'parent_code' => '70'],
            ['code' => '70008', 'name' => 'PROD. INT. GRAMINEAS Y LEGUMIN', 'parent_code' => '70'],
            ['code' => '70009', 'name' => 'PROD. INT. BIOTECNOLOGIA', 'parent_code' => '70'],
            ['code' => '70010', 'name' => 'PROD. INT. ORNAMENTALES', 'parent_code' => '70'],
            ['code' => '70011', 'name' => 'PROD. INT. RES', 'parent_code' => '70'],
            ['code' => '71000', 'name' => 'DONACION DGSVA', 'parent_code' => '71'],
            ['code' => '71001', 'name' => 'DONACION ECONOMIA AGROPECUARIA', 'parent_code' => '71'],
            ['code' => '71002', 'name' => 'DONACION SIBASI SAN MIGUEL', 'parent_code' => '71'],
            ['code' => '71003', 'name' => 'DONAC. SEMIL. CRISTIANI BURK.', 'parent_code' => '71'],
            ['code' => '71004', 'name' => 'DONACION NUTRIFER', 'parent_code' => '71'],
            ['code' => '71005', 'name' => 'DONACION CENTA', 'parent_code' => '71'],
            ['code' => '71006', 'name' => 'DONAC.INGENIO EL ANGEL', 'parent_code' => '71'],
            ['code' => '71007', 'name' => 'DONAC.IMPRESSA REPUESTOS', 'parent_code' => '71'],
            ['code' => '71008', 'name' => 'DONACION QUIMICA VISION', 'parent_code' => '71'],
            ['code' => '71009', 'name' => 'DONACION BIOFERME', 'parent_code' => '71'],
            ['code' => '71010', 'name' => 'DON.SISTEMA DE SEGURIDAD', 'parent_code' => '71'],
            ['code' => '71011', 'name' => 'DON.AUTOR.AVIACION CIVIL', 'parent_code' => '71'],
            ['code' => '71012', 'name' => 'DONACION CLASICOS ROXIL SA DE', 'parent_code' => '71'],
            ['code' => '71013', 'name' => 'DONACION PROSELA', 'parent_code' => '71'],
            ['code' => '71014', 'name' => 'DONACION PROYECTO ACUICOLA MAG', 'parent_code' => '71'],
            ['code' => '71015', 'name' => 'DONACION BAYER', 'parent_code' => '71'],
            ['code' => '71016', 'name' => 'DONACION MISION TECNICA TAIWAN', 'parent_code' => '71'],
            ['code' => '71017', 'name' => 'DONACION TECNUTRAL', 'parent_code' => '71'],
            ['code' => '71018', 'name' => 'DON. DISTRIB. UNIV. HERNANDEZ', 'parent_code' => '71'],
            ['code' => '71019', 'name' => 'DONACION MC. CORMICK', 'parent_code' => '71'],
            ['code' => '71020', 'name' => 'DONACION LACTOSA DE CV', 'parent_code' => '71'],
            ['code' => '71021', 'name' => 'DON. IMPRESOS UNIVERSITARIO', 'parent_code' => '71'],
            ['code' => '71022', 'name' => 'KARLA MARIA GALDAMEZ CASTANEDA', 'parent_code' => '71'],
            ['code' => '71023', 'name' => 'DONACION JOSE ALBERTO MORALES', 'parent_code' => '71'],
            ['code' => '71024', 'name' => 'DON. EMBAJADA DE CHINA -TAIWAN', 'parent_code' => '71'],
            ['code' => '71025', 'name' => 'DONAC. BANCO DE FOMENTO AGROPE', 'parent_code' => '71'],
            ['code' => '71026', 'name' => 'DONACION USAID/CUERPOS DE PAZ', 'parent_code' => '71'],
            ['code' => '71027', 'name' => 'DONACION MAG', 'parent_code' => '71'],
            ['code' => '71028', 'name' => 'DONACION DUWEST EL SALVADOR', 'parent_code' => '71'],
            ['code' => '71029', 'name' => 'DONACION MAG - MATAZANO', 'parent_code' => '71'],
            ['code' => '71030', 'name' => 'DONACION FAO', 'parent_code' => '71'],
            ['code' => '71031', 'name' => 'DONACION QUIMICAS UNIDAS SALV.', 'parent_code' => '71'],
            ['code' => '71032', 'name' => 'DONACION DIAGRI SA DE CV', 'parent_code' => '71'],
            ['code' => '71033', 'name' => 'DONACION AGRINTER', 'parent_code' => '71'],
            ['code' => '71034', 'name' => 'DONACION GENETICA GANADERA', 'parent_code' => '71'],
            ['code' => '71035', 'name' => 'DON. ECOMIL CONSULTORES DE CA', 'parent_code' => '71'],
            ['code' => '71036', 'name' => 'DON, EXPORTADORA PACAS MARTINE', 'parent_code' => '71'],
            ['code' => '71037', 'name' => 'DONACION UNIFERSA - DISAGRO SA', 'parent_code' => '71'],
            ['code' => '71038', 'name' => 'DONACION NATHALIE FASHION', 'parent_code' => '71'],
            ['code' => '71039', 'name' => 'DONACION AFP CONFIA', 'parent_code' => '71'],
            ['code' => '71040', 'name' => 'DONACION ANDALUCIA SA DE CV', 'parent_code' => '71'],
            ['code' => '71042', 'name' => 'DON. COMP. GENERAL DE EQUIPOS', 'parent_code' => '71'],
            ['code' => '71043', 'name' => 'DONACION ARTE Y COLOR PUBLIC.', 'parent_code' => '71'],
            ['code' => '71044', 'name' => 'DONACION IMACASA', 'parent_code' => '71'],
            ['code' => '71045', 'name' => 'DONACION AFP CRECER', 'parent_code' => '71'],
            ['code' => '71046', 'name' => 'DON. ASOC. DE AVIC. DE EL SAL', 'parent_code' => '71'],
            ['code' => '71047', 'name' => 'DON. C. ASIST. DENTAL MEYER SA', 'parent_code' => '71'],
            ['code' => '71048', 'name' => 'DONACION FRANCISCO FLORES RECI', 'parent_code' => '71'],
            ['code' => '71049', 'name' => 'DONACION VIVERO MUNDO VERDE', 'parent_code' => '71'],
            ['code' => '71050', 'name' => 'DONACION CRIAVES SA DE CV', 'parent_code' => '71'],
            ['code' => '71051', 'name' => 'DONACION DOCUMENTOS INTELIGENT', 'parent_code' => '71'],
            ['code' => '71052', 'name' => 'DON. CONSEJO NACIONAL DE ENERG', 'parent_code' => '71'],
            ['code' => '71053', 'name' => 'DONAC. RENE ALEJANDRO HIDALGO', 'parent_code' => '71'],
            ['code' => '71054', 'name' => 'DON. FUPEC-MUES-AAGENAEX-ENA', 'parent_code' => '71'],
            ['code' => '71055', 'name' => 'DONACION AQUA ENGINEERING', 'parent_code' => '71'],
            ['code' => '71056', 'name' => 'DONACION MULTIPECUARIOS SA DE', 'parent_code' => '71'],
            ['code' => '71057', 'name' => 'DONACION DISTRIBUIDORA JAGUAR', 'parent_code' => '71'],
            ['code' => '71058', 'name' => 'DONACION TEXTILES MAS SA DE CV', 'parent_code' => '71'],
            ['code' => '71059', 'name' => 'DONACION JOSE ROLANDO ESCOBAR', 'parent_code' => '71'],
            ['code' => '71060', 'name' => 'DONACION SYGENTA', 'parent_code' => '71'],
            ['code' => '71061', 'name' => 'DONACION SAENA', 'parent_code' => '71'],
            ['code' => '71062', 'name' => 'DONACION GUMARSAL', 'parent_code' => '71'],
            ['code' => '71063', 'name' => 'DONAC. UNIFORMES GABRIELA SA D', 'parent_code' => '71'],
            ['code' => '71064', 'name' => 'DONAC. MARIA MAGDALENA AUCEDA', 'parent_code' => '71'],
            ['code' => '71065', 'name' => 'DONACION BANCO DAVIVIENDA', 'parent_code' => '71'],
            ['code' => '71066', 'name' => 'DONACION INDUSTRIAS PICHINTE', 'parent_code' => '71'],
            ['code' => '71067', 'name' => 'DONACION EL SURCO SA DE CV', 'parent_code' => '71'],
            ['code' => '71068', 'name' => 'DON. CAJA DE CREDITO DE OPICO', 'parent_code' => '71'],
            ['code' => '71069', 'name' => 'DONACION CENDEPESCA', 'parent_code' => '71'],
            ['code' => '71070', 'name' => 'DONACION LEAGUE CA LTDA DE CV', 'parent_code' => '71'],
            ['code' => '71071', 'name' => 'DONACION FELIPE ALFREDO CERON', 'parent_code' => '71'],
            ['code' => '71072', 'name' => 'DONACION CONSULTEF SA DE CV', 'parent_code' => '71'],
            ['code' => '71073', 'name' => 'DONACION SARAM SA DE CV', 'parent_code' => '71'],
            ['code' => '71074', 'name' => 'DONACION DISAGRO', 'parent_code' => '71'],
            ['code' => '71075', 'name' => 'DONACION DNICTI - MINED', 'parent_code' => '71'],
            ['code' => '71076', 'name' => 'DON. INGENIO AZUCARERO JIBOA', 'parent_code' => '71'],
            ['code' => '71077', 'name' => 'DONACION MARIA DEL ROSARIO MED', 'parent_code' => '71'],
            ['code' => '71078', 'name' => 'DONACION BLANCA CANENGUEZ DE Z', 'parent_code' => '71'],
            ['code' => '71079', 'name' => 'DON. SWISSCONTACT', 'parent_code' => '71'],
            ['code' => '71080', 'name' => 'DON. PRIMER BANCO DE LOS TRABA', 'parent_code' => '71'],
            ['code' => '71081', 'name' => 'DONACION EX ALUMNOS PROM. IX', 'parent_code' => '71'],
            ['code' => '71082', 'name' => 'DONACION AEROMANTENIMIENTO, S.', 'parent_code' => '71'],
            ['code' => '71083', 'name' => 'DON. MIGUEL ANGEL W. MEJIA', 'parent_code' => '71'],
            ['code' => '71084', 'name' => 'DON. CORTE DE CUENTAS DE LA RE', 'parent_code' => '71'],
            ['code' => '71085', 'name' => 'DONACION FUNDATER', 'parent_code' => '71'],
            ['code' => '71086', 'name' => 'DONACION LUIS ESCOBAR', 'parent_code' => '71'],
            ['code' => '71088', 'name' => 'SERVICIOS FINANCIEROS ENLACE', 'parent_code' => '71'],
            ['code' => '71089', 'name' => 'DON. EX ALUMNOS PROM XXI', 'parent_code' => '71'],
            ['code' => '71090', 'name' => 'DONACION JOSE ALEX MARTINEZ', 'parent_code' => '71'],
            ['code' => '71091', 'name' => 'DONACION PROMOCION XI EX ALUMN', 'parent_code' => '71'],
            ['code' => '71092', 'name' => 'DONACION PROM. XXX EX ALUMNOS', 'parent_code' => '71'],
            ['code' => '71093', 'name' => 'DONACION PROMOCION XVII', 'parent_code' => '71'],
            ['code' => '71094', 'name' => 'DONACION ING SALVADOR GONZALE', 'parent_code' => '71'],
            ['code' => '71095', 'name' => 'DONACION PROMOXION XXV EX ALUM', 'parent_code' => '71'],
            ['code' => '71096', 'name' => 'DON. SERVICIO AGRICOLA Y ZOOTE', 'parent_code' => '71'],
            ['code' => '71097', 'name' => 'DON. SHERWIN WILLIAMS DE CENTR', 'parent_code' => '71'],
            ['code' => '71098', 'name' => 'COMERCIAL INDUSTRIAL OLINS, S.', 'parent_code' => '71'],
            ['code' => '71099', 'name' => 'MIGUEL ANGEL MEJIA DONACION', 'parent_code' => '71'],
            ['code' => '71100', 'name' => 'CHAVEZ IMPRESORES', 'parent_code' => '71'],
            ['code' => '71101', 'name' => 'SOMBREROS DE RODEO 4X4', 'parent_code' => '71'],
            ['code' => '71102', 'name' => 'PRODUCTOS TECNOLOGICOS, S.A. D', 'parent_code' => '71'],
            ['code' => '71103', 'name' => 'ELECTRO FERRETERA, S.A. DE C.V', 'parent_code' => '71'],
            ['code' => '71104', 'name' => 'DON. ALAS DORADAS, S.A. DE C.V', 'parent_code' => '71'],
            ['code' => '71105', 'name' => 'DON. SISTEMAS ECOLOGICOS, SA D', 'parent_code' => '71'],
            ['code' => '71106', 'name' => 'DON. VILLAVAR, S.A. DE C.V.', 'parent_code' => '71'],
            ['code' => '71107', 'name' => 'DON. SELLO DE ORO S.A. DE C.V.', 'parent_code' => '71'],
            ['code' => '71108', 'name' => 'DON. CRUZ ASESORES', 'parent_code' => '71'],
            ['code' => '71109', 'name' => 'DON. ING JOSE MIGUEL CAMBARA Z', 'parent_code' => '71'],
            ['code' => '71110', 'name' => 'DONACION FUNDAZUCAR', 'parent_code' => '71'],
            ['code' => '71111', 'name' => 'DON. CONCENTRADOS ALIANSA', 'parent_code' => '71'],
            ['code' => '71112', 'name' => 'DONACION FORAGRO EL SALVADOR', 'parent_code' => '71'],
            ['code' => '71113', 'name' => 'DON. SERVICIO AGRICOLA SALVADO', 'parent_code' => '71'],
            ['code' => '71114', 'name' => 'DON. SUMINISTROS GENESIS, S.A.', 'parent_code' => '71'],
            ['code' => '71115', 'name' => 'DON. AVICOLA SALVADORENA, S.A.', 'parent_code' => '71'],
            ['code' => '71116', 'name' => 'DON. UNION COMERCIAL DE EL SAL', 'parent_code' => '71'],
            ['code' => '71117', 'name' => 'DON. AFP CRECER', 'parent_code' => '71'],
            ['code' => '71118', 'name' => 'DON. SEGUROS E INVERSIONES, S.', 'parent_code' => '71'],
            ['code' => '71119', 'name' => 'DON. AGROAMIGO (OSCAR ALBERTO', 'parent_code' => '71'],
            ['code' => '71120', 'name' => 'DON. BANCO AGRICOLA', 'parent_code' => '71'],
            ['code' => '71121', 'name' => 'AGROFERRETERIA EL BOSQUE', 'parent_code' => '71'],
            ['code' => '71122', 'name' => 'DON. AGRICOLA GANADERA BORJA L', 'parent_code' => '71'],
            ['code' => '71123', 'name' => 'YARA GUATEMALA', 'parent_code' => '71'],
            ['code' => '71124', 'name' => 'DON. LA SULTANA, S.A. DE C.V.', 'parent_code' => '71'],
            ['code' => '71125', 'name' => 'ASOCIACION DE AVICULTORES DE E', 'parent_code' => '71'],
            ['code' => '71126', 'name' => 'AMANECER RURAL', 'parent_code' => '71'],
            ['code' => '71127', 'name' => 'DONACION GERVIN MANUEL RIVERA', 'parent_code' => '71'],
            ['code' => '71128', 'name' => 'DONACION DISATYR', 'parent_code' => '71'],
            ['code' => '71129', 'name' => 'DONACION PROM. XIV EX ALUMNOS', 'parent_code' => '71'],
            ['code' => '71130', 'name' => 'DON. PH.D. ODETTE VARELA MILLA', 'parent_code' => '71'],
            ['code' => '71131', 'name' => 'DON. AGROINDUSTRIAS BUENAVISTA', 'parent_code' => '71'],
            ['code' => '71132', 'name' => 'DON. SERV. DE TRANSPORTE Y PRO', 'parent_code' => '71'],
            ['code' => '71133', 'name' => 'DON. GRUPO ROMERO ORTIZ, S.A.', 'parent_code' => '71'],
            ['code' => '71134', 'name' => 'DON. INVERSIONES LA JOYA, S.A.', 'parent_code' => '71'],
            ['code' => '71135', 'name' => 'DON. INVERSIONES VIDA, S.A. DE', 'parent_code' => '71'],
            ['code' => '71136', 'name' => 'TERMOENCOGIBLES, S.A. DE C.V.', 'parent_code' => '71'],
            ['code' => '71137', 'name' => 'DATAPLEX EL SALVADOR, S.A. DE', 'parent_code' => '71'],
            ['code' => '71138', 'name' => 'DONACION PROM. XXXVII EX ALUMN', 'parent_code' => '71'],
            ['code' => '71139', 'name' => 'DONACION PROMOCION XXIII EX AL', 'parent_code' => '71'],
            ['code' => '71140', 'name' => 'DONACION UNIVERSIDAD DE TEXAS', 'parent_code' => '71'],
            ['code' => '71141', 'name' => 'DONACION PROTECCION CIVIL', 'parent_code' => '71'],
            ['code' => '71142', 'name' => 'PROM. XLVI EX ALUMNOS ENA', 'parent_code' => '71'],
            ['code' => '71143', 'name' => 'GRUPO QL, S.A. DE C.V. - DONAC', 'parent_code' => '71'],
            ['code' => '71145', 'name' => 'DIR. GRAL DESARROLLO RURAL MAG', 'parent_code' => '71'],
            ['code' => '71146', 'name' => 'DON. OSCAR ALBERTO FLORES MENJ', 'parent_code' => '71'],
            ['code' => '71147', 'name' => 'DON. COSAVI, DE R.L.', 'parent_code' => '71'],
            ['code' => '71148', 'name' => 'DON. DOMINGO ANTONIO MEDRANO', 'parent_code' => '71'],
            ['code' => '71149', 'name' => 'DON. CEL COMISION EJECUTIVA HI', 'parent_code' => '71'],
            ['code' => '71150', 'name' => 'DON. TERCER ANO 2022', 'parent_code' => '71'],
            ['code' => '71151', 'name' => 'DON. DISTRIBUIDORA DE ALIMENTO', 'parent_code' => '71'],
            ['code' => '71152', 'name' => 'DON. PROM. XXVI EX ALUMNOS', 'parent_code' => '71'],
            ['code' => '71153', 'name' => 'DON. PROMO XX AGRONOMOS ENA 19', 'parent_code' => '71'],
            ['code' => '71154', 'name' => 'DONACION SISTEMAS DE ENFRIAMIE', 'parent_code' => '71'],
            ['code' => '71155', 'name' => 'DONACION ING JOSE ANTONIO MENA', 'parent_code' => '71'],
            ['code' => '71156', 'name' => 'DONACION IICA', 'parent_code' => '71'],
            ['code' => '71157', 'name' => 'DON. ING SIGFREDO CORADO', 'parent_code' => '71'],
            ['code' => '71158', 'name' => 'DON. CJA DE CREDITO SAN PEDRO', 'parent_code' => '71'],
            ['code' => '71159', 'name' => 'DON. SERV. FINANC. ENLACE', 'parent_code' => '71'],
            ['code' => '71160', 'name' => 'DON. PROM. XVI EX ALUMNOS ENA', 'parent_code' => '71'],
            ['code' => '71161', 'name' => 'DON. FREUND, S.A. DE C.V.', 'parent_code' => '71'],
            ['code' => '71162', 'name' => 'DON.CRISTYAN JOSUE MORALES H.', 'parent_code' => '71'],
            ['code' => '71163', 'name' => 'DONACION MAQUIPART', 'parent_code' => '71'],
            ['code' => '71164', 'name' => 'DON.JOSUE NEFTALY LOZANO ACOST', 'parent_code' => '71'],
            ['code' => '71165', 'name' => 'DON SEBASTIAN APARICIO CRUZ R', 'parent_code' => '71'],
            ['code' => '71166', 'name' => 'DON JOSE ANTONIO MOLINA RIVAS', 'parent_code' => '71'],
            ['code' => '71167', 'name' => 'DON MANUEL ANTONIO VASQUEZ ORT', 'parent_code' => '71'],
            ['code' => '71168', 'name' => 'DON MONICA MARIA AUXILIADORA G', 'parent_code' => '71'],
            ['code' => '71169', 'name' => 'DON SONIA MELISSA ESPINAL PERL', 'parent_code' => '71'],
            ['code' => '71170', 'name' => 'DON ELI SAUL ALVARADO SANDOVAL', 'parent_code' => '71'],
            ['code' => '71171', 'name' => 'DON ROSA ELVIRA ALFARO DE ORE', 'parent_code' => '71'],
            ['code' => '71172', 'name' => 'DON COMITE AMBIENTAL EMPRESARI', 'parent_code' => '71'],
            ['code' => '71173', 'name' => 'DONACION PROLECHE', 'parent_code' => '71'],
            ['code' => '71174', 'name' => 'DON.DIST DE PINTURAS Y MAT. L', 'parent_code' => '71'],
            ['code' => '72000', 'name' => 'CONVENIO BIOFERME', 'parent_code' => '72'],
            ['code' => '72001', 'name' => 'CONVENIO PAHNAS ENA', 'parent_code' => '72'],
            ['code' => '72002', 'name' => 'CONVENIO CRIAVES', 'parent_code' => '72'],
            ['code' => '73000', 'name' => 'AUTOCONSUMO', 'parent_code' => '73'],
            ['code' => '74000', 'name' => 'PROD. ESPECIALES SALVADORENOS', 'parent_code' => '74'],
            ['code' => '75000', 'name' => 'PROYECTO PEIS N 4643', 'parent_code' => '75'],
            ['code' => '75001', 'name' => 'PROYECTO AFECTADOS HURACAN IDA', 'parent_code' => '75'],
            ['code' => '75002', 'name' => 'PROYECTO 6882 ESPECIFICO 61102', 'parent_code' => '75'],
            ['code' => '76001', 'name' => 'PERMUTAS', 'parent_code' => '76'],
            ['code' => '77001', 'name' => 'REINGR. A BODEGA S/ACUERDO DIR', 'parent_code' => '77'],
            ['code' => '78001', 'name' => 'C/DE BIENES A T/FONDO MANTENIM', 'parent_code' => '78'],
            ['code' => '79000', 'name' => 'FONDOS INJUVE', 'parent_code' => '79'],
            ['code' => '80001', 'name' => 'PRODUCTO TERMINADO TIENDA', 'parent_code' => '80'],
        ];
    }
}
