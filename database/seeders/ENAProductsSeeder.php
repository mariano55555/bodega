<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ENAProductsSeeder extends Seeder
{
    /**
     * Seed 30 key products for ENA warehouse management system across 5 categories.
     */
    public function run(): void
    {
        $this->command->info('📦 Creando productos clave para la ENA...');

        $company = DB::table('companies')->where('slug', 'escuela-nacional-agricultura')->first();

        if (! $company) {
            $this->command->error('❌ Error: Empresa ENA no encontrada. Ejecute ENACompanySeeder primero.');

            return;
        }

        // Get or create product categories
        $categories = $this->ensureCategories($company->id);

        // Get units of measure
        $units = $this->getUnitsOfMeasure();

        // Define 30 products across 5 categories
        $products = [
            // CATEGORÍA 1: INSUMOS AGRÍCOLAS (7 productos)
            [
                'code' => 'PRO-001',
                'name' => 'Fertilizante NPK 15-15-15',
                'description' => 'Fertilizante balanceado para cultivos generales. Contiene Nitrógeno, Fósforo y Potasio en partes iguales. Presentación en sacos de 50 lb.',
                'category' => 'insumos-agricolas',
                'unit' => 'Saco',
                'cost_price' => 25.00,
                'selling_price' => 30.00,
                'min_stock' => 20,
                'max_stock' => 100,
            ],
            [
                'code' => 'PRO-002',
                'name' => 'Fertilizante Urea 46%',
                'description' => 'Fertilizante nitrogenado de alta concentración para crecimiento vegetativo. Sacos de 50 lb.',
                'category' => 'insumos-agricolas',
                'unit' => 'Saco',
                'cost_price' => 28.00,
                'selling_price' => 34.00,
                'min_stock' => 15,
                'max_stock' => 80,
            ],
            [
                'code' => 'PRO-003',
                'name' => 'Semilla Maíz Híbrido H-59',
                'description' => 'Semilla de maíz híbrido de alto rendimiento, adaptada al clima salvadoreño. Ciclo de 120 días.',
                'category' => 'insumos-agricolas',
                'unit' => 'Kilogramo',
                'cost_price' => 9.00,
                'selling_price' => 12.00,
                'min_stock' => 50,
                'max_stock' => 300,
            ],
            [
                'code' => 'PRO-004',
                'name' => 'Semilla Frijol Rojo',
                'description' => 'Semilla certificada de frijol rojo para siembra. Variedad mejorada con resistencia a sequía.',
                'category' => 'insumos-agricolas',
                'unit' => 'Kilogramo',
                'cost_price' => 3.50,
                'selling_price' => 5.00,
                'min_stock' => 100,
                'max_stock' => 500,
            ],
            [
                'code' => 'PRO-005',
                'name' => 'Insecticida Cipermetrina 25%',
                'description' => 'Insecticida piretroide de amplio espectro para control de plagas en cultivos. Presentación líquida.',
                'category' => 'insumos-agricolas',
                'unit' => 'Litro',
                'cost_price' => 15.00,
                'selling_price' => 20.00,
                'min_stock' => 10,
                'max_stock' => 50,
            ],
            [
                'code' => 'PRO-006',
                'name' => 'Herbicida Glifosato 48%',
                'description' => 'Herbicida sistémico no selectivo para control de malezas de hoja ancha y angosta.',
                'category' => 'insumos-agricolas',
                'unit' => 'Litro',
                'cost_price' => 12.00,
                'selling_price' => 16.00,
                'min_stock' => 15,
                'max_stock' => 60,
            ],
            [
                'code' => 'PRO-007',
                'name' => 'Fungicida Mancozeb 80%',
                'description' => 'Fungicida de contacto preventivo para enfermedades foliares en cultivos.',
                'category' => 'insumos-agricolas',
                'unit' => 'Kilogramo',
                'cost_price' => 8.50,
                'selling_price' => 11.00,
                'min_stock' => 20,
                'max_stock' => 80,
            ],

            // CATEGORÍA 2: ALIMENTOS PARA GANADO (5 productos)
            [
                'code' => 'PRO-008',
                'name' => 'Concentrado Ganado Bovino',
                'description' => 'Alimento concentrado balanceado para ganado bovino de engorde. 18% proteína. Sacos 100 lb.',
                'category' => 'alimentos-ganado',
                'unit' => 'Saco',
                'cost_price' => 32.00,
                'selling_price' => 38.00,
                'min_stock' => 15,
                'max_stock' => 60,
            ],
            [
                'code' => 'PRO-009',
                'name' => 'Concentrado Ganado Porcino',
                'description' => 'Alimento concentrado para cerdos en crecimiento y acabado. 16% proteína. Sacos 100 lb.',
                'category' => 'alimentos-ganado',
                'unit' => 'Saco',
                'cost_price' => 30.00,
                'selling_price' => 36.00,
                'min_stock' => 10,
                'max_stock' => 50,
            ],
            [
                'code' => 'PRO-010',
                'name' => 'Concentrado Avícola Ponedoras',
                'description' => 'Alimento completo para aves ponedoras en producción. 17% proteína, alto en calcio. Sacos 100 lb.',
                'category' => 'alimentos-ganado',
                'unit' => 'Saco',
                'cost_price' => 35.00,
                'selling_price' => 42.00,
                'min_stock' => 12,
                'max_stock' => 55,
            ],
            [
                'code' => 'PRO-011',
                'name' => 'Sales Minerales',
                'description' => 'Suplemento mineral para ganado. Bloques de 5 kg con macro y microelementos esenciales.',
                'category' => 'alimentos-ganado',
                'unit' => 'Bloque',
                'cost_price' => 8.00,
                'selling_price' => 10.00,
                'min_stock' => 30,
                'max_stock' => 120,
            ],
            [
                'code' => 'PRO-012',
                'name' => 'Melaza',
                'description' => 'Melaza de caña para suplementación energética del ganado. Galones.',
                'category' => 'alimentos-ganado',
                'unit' => 'Galón',
                'cost_price' => 4.50,
                'selling_price' => 6.00,
                'min_stock' => 40,
                'max_stock' => 150,
            ],

            // CATEGORÍA 3: HERRAMIENTAS (5 productos)
            [
                'code' => 'PRO-013',
                'name' => 'Palas de punta',
                'description' => 'Pala agrícola de punta con mango de madera. Para excavación y trabajo de campo.',
                'category' => 'herramientas',
                'unit' => 'Pieza',
                'cost_price' => 12.00,
                'selling_price' => 15.00,
                'min_stock' => 10,
                'max_stock' => 40,
            ],
            [
                'code' => 'PRO-014',
                'name' => 'Azadones',
                'description' => 'Azadón para cultivo con mango de madera resistente. Ideal para labores de deshierbe.',
                'category' => 'herramientas',
                'unit' => 'Pieza',
                'cost_price' => 10.00,
                'selling_price' => 13.00,
                'min_stock' => 15,
                'max_stock' => 50,
            ],
            [
                'code' => 'PRO-015',
                'name' => 'Machetes',
                'description' => 'Machete de 24 pulgadas con funda. Herramienta esencial para trabajo agrícola.',
                'category' => 'herramientas',
                'unit' => 'Pieza',
                'cost_price' => 8.00,
                'selling_price' => 11.00,
                'min_stock' => 20,
                'max_stock' => 60,
            ],
            [
                'code' => 'PRO-016',
                'name' => 'Rastrillos',
                'description' => 'Rastrillo metálico de 14 dientes con mango de madera para preparación de suelo.',
                'category' => 'herramientas',
                'unit' => 'Pieza',
                'cost_price' => 9.00,
                'selling_price' => 12.00,
                'min_stock' => 12,
                'max_stock' => 45,
            ],
            [
                'code' => 'PRO-017',
                'name' => 'Tijeras de podar',
                'description' => 'Tijeras de podar profesionales con hoja de acero al carbono. Para poda de árboles frutales.',
                'category' => 'herramientas',
                'unit' => 'Pieza',
                'cost_price' => 18.00,
                'selling_price' => 24.00,
                'min_stock' => 8,
                'max_stock' => 30,
            ],

            // CATEGORÍA 4: PROCESAMIENTO (6 productos)
            [
                'code' => 'PRO-018',
                'name' => 'Azúcar blanca',
                'description' => 'Azúcar refinada grado alimenticio para procesamiento de mermeladas y jaleas. Quintales.',
                'category' => 'procesamiento',
                'unit' => 'Quintal',
                'cost_price' => 35.00,
                'selling_price' => 42.00,
                'min_stock' => 10,
                'max_stock' => 50,
            ],
            [
                'code' => 'PRO-019',
                'name' => 'Sal común',
                'description' => 'Sal refinada grado alimenticio para procesamiento y conservación de alimentos.',
                'category' => 'procesamiento',
                'unit' => 'Kilogramo',
                'cost_price' => 0.50,
                'selling_price' => 0.80,
                'min_stock' => 100,
                'max_stock' => 400,
            ],
            [
                'code' => 'PRO-020',
                'name' => 'Conservantes',
                'description' => 'Benzoato de sodio como conservante para productos agroindustriales.',
                'category' => 'procesamiento',
                'unit' => 'Kilogramo',
                'cost_price' => 12.00,
                'selling_price' => 16.00,
                'min_stock' => 5,
                'max_stock' => 25,
            ],
            [
                'code' => 'PRO-021',
                'name' => 'Envases de vidrio 250ml',
                'description' => 'Frascos de vidrio con tapa metálica para envasado de mermeladas y productos procesados.',
                'category' => 'procesamiento',
                'unit' => 'Pieza',
                'cost_price' => 0.45,
                'selling_price' => 0.65,
                'min_stock' => 500,
                'max_stock' => 2000,
            ],
            [
                'code' => 'PRO-022',
                'name' => 'Etiquetas adhesivas',
                'description' => 'Rollos de etiquetas adhesivas para productos agroindustriales. 1000 etiquetas por rollo.',
                'category' => 'procesamiento',
                'unit' => 'Rollo',
                'cost_price' => 15.00,
                'selling_price' => 20.00,
                'min_stock' => 10,
                'max_stock' => 50,
            ],
            [
                'code' => 'PRO-023',
                'name' => 'Levadura fresca',
                'description' => 'Levadura fresca para panadería y procesamiento de alimentos. Requiere refrigeración.',
                'category' => 'procesamiento',
                'unit' => 'Kilogramo',
                'cost_price' => 12.00,
                'selling_price' => 16.00,
                'min_stock' => 5,
                'max_stock' => 20,
            ],

            // CATEGORÍA 5: MANTENIMIENTO (7 productos)
            [
                'code' => 'PRO-024',
                'name' => 'Pintura látex blanco',
                'description' => 'Pintura látex blanco mate para interiores y exteriores. Galones.',
                'category' => 'mantenimiento',
                'unit' => 'Galón',
                'cost_price' => 18.00,
                'selling_price' => 24.00,
                'min_stock' => 10,
                'max_stock' => 40,
            ],
            [
                'code' => 'PRO-025',
                'name' => 'Cemento gris',
                'description' => 'Cemento Portland gris tipo I para construcción. Sacos de 42.5 kg.',
                'category' => 'mantenimiento',
                'unit' => 'Saco',
                'cost_price' => 8.50,
                'selling_price' => 11.00,
                'min_stock' => 20,
                'max_stock' => 100,
            ],
            [
                'code' => 'PRO-026',
                'name' => 'Clavos 3"',
                'description' => 'Clavos de acero galvanizado de 3 pulgadas para carpintería y construcción.',
                'category' => 'mantenimiento',
                'unit' => 'Libra',
                'cost_price' => 1.20,
                'selling_price' => 1.80,
                'min_stock' => 50,
                'max_stock' => 200,
            ],
            [
                'code' => 'PRO-027',
                'name' => 'Aceite motor 15W40',
                'description' => 'Aceite mineral para motores diésel de tractores y maquinaria agrícola. Galones.',
                'category' => 'mantenimiento',
                'unit' => 'Galón',
                'cost_price' => 22.00,
                'selling_price' => 28.00,
                'min_stock' => 15,
                'max_stock' => 60,
            ],
            [
                'code' => 'PRO-028',
                'name' => 'Candados de seguridad',
                'description' => 'Candados de alta seguridad para puertas y portones. Llave maestra.',
                'category' => 'mantenimiento',
                'unit' => 'Pieza',
                'cost_price' => 8.00,
                'selling_price' => 12.00,
                'min_stock' => 10,
                'max_stock' => 40,
            ],
            [
                'code' => 'PRO-029',
                'name' => 'Alambre de púas',
                'description' => 'Rollo de alambre de púas galvanizado para cercas. 200 metros por rollo.',
                'category' => 'mantenimiento',
                'unit' => 'Rollo',
                'cost_price' => 45.00,
                'selling_price' => 55.00,
                'min_stock' => 5,
                'max_stock' => 25,
            ],
            [
                'code' => 'PRO-030',
                'name' => 'Bombillos LED 15W',
                'description' => 'Bombillos LED de 15W luz blanca para instalaciones del campus. Bajo consumo.',
                'category' => 'mantenimiento',
                'unit' => 'Pieza',
                'cost_price' => 3.50,
                'selling_price' => 5.00,
                'min_stock' => 50,
                'max_stock' => 200,
            ],
        ];

        $totalProducts = 0;
        foreach ($products as $productData) {
            $category = $categories[$productData['category']] ?? null;
            $unit = $units[$productData['unit']] ?? null;

            if (! $category || ! $unit) {
                $this->command->warn("⚠ Saltando producto {$productData['code']}: categoría o unidad no encontrada");

                continue;
            }

            // Get the unit abbreviation for the varchar field
            $unitObj = DB::table('units_of_measure')->where('id', $unit)->first();

            DB::table('products')->insert([
                'company_id' => $company->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'sku' => $productData['code'],
                'description' => $productData['description'],
                'category_id' => $category,
                'unit_of_measure_id' => $unit,
                'unit_of_measure' => $unitObj ? $unitObj->abbreviation : 'pz',
                'cost' => $productData['cost_price'],
                'price' => $productData['selling_price'],
                'track_inventory' => true,
                'is_active' => true,
                'active_at' => now(),
                'valuation_method' => 'fifo',
                'minimum_stock' => $productData['min_stock'],
                'maximum_stock' => $productData['max_stock'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalProducts++;
            $this->command->line("✓ {$productData['code']} - {$productData['name']}");
        }

        $this->command->info("✅ {$totalProducts} productos creados exitosamente");
        $this->command->line('   - 7 Insumos Agrícolas');
        $this->command->line('   - 5 Alimentos para Ganado');
        $this->command->line('   - 5 Herramientas');
        $this->command->line('   - 6 Procesamiento');
        $this->command->line('   - 7 Mantenimiento');
    }

    /**
     * Ensure product categories exist, create if missing.
     */
    private function ensureCategories(int $companyId): array
    {
        $categoryMap = [];

        $categories = [
            [
                'slug' => 'insumos-agricolas',
                'name' => 'Insumos Agrícolas',
                'code' => 'ENA-INS',
                'description' => 'Fertilizantes, semillas, agroquímicos para producción agrícola',
            ],
            [
                'slug' => 'alimentos-ganado',
                'name' => 'Alimentos para Ganado',
                'code' => 'ENA-ALI',
                'description' => 'Concentrados, sales minerales y suplementos para animales',
            ],
            [
                'slug' => 'herramientas',
                'name' => 'Herramientas',
                'code' => 'ENA-HER',
                'description' => 'Herramientas manuales y equipos para trabajo agrícola',
            ],
            [
                'slug' => 'procesamiento',
                'name' => 'Procesamiento',
                'code' => 'ENA-PRO',
                'description' => 'Insumos para planta agroindustrial y procesamiento de alimentos',
            ],
            [
                'slug' => 'mantenimiento',
                'name' => 'Mantenimiento',
                'code' => 'ENA-MAN',
                'description' => 'Materiales de construcción, herramientas y repuestos',
            ],
        ];

        foreach ($categories as $cat) {
            $existing = DB::table('product_categories')
                ->where('company_id', $companyId)
                ->where('slug', $cat['slug'])
                ->first();

            if ($existing) {
                $categoryMap[$cat['slug']] = $existing->id;
            } else {
                $categoryId = DB::table('product_categories')->insertGetId([
                    'company_id' => $companyId,
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'code' => $cat['code'],
                    'description' => $cat['description'],
                    'is_active' => true,
                    'active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $categoryMap[$cat['slug']] = $categoryId;
            }
        }

        return $categoryMap;
    }

    /**
     * Get units of measure mapped by name to their IDs.
     */
    private function getUnitsOfMeasure(): array
    {
        $unitMap = [];

        $unitNames = [
            'Kilogramo',
            'Litro',
            'Pieza',
            'Galón',
            'Libra',
            'Saco',
            'Quintal',
            'Rollo',
            'Bloque',
        ];

        foreach ($unitNames as $unitName) {
            $unit = DB::table('units_of_measure')
                ->where('name', $unitName)
                ->orWhere('abbreviation', $unitName)
                ->first();

            if ($unit) {
                $unitMap[$unitName] = $unit->id;
            }
        }

        // Manual mapping for units that might not exist exactly as named
        // If 'Saco' doesn't exist, we'll use 'paq' (Paquete) or create custom logic
        if (! isset($unitMap['Saco'])) {
            $paquete = DB::table('units_of_measure')->where('abbreviation', 'paq')->first();
            if ($paquete) {
                $unitMap['Saco'] = $paquete->id;
            }
        }

        if (! isset($unitMap['Quintal'])) {
            // 1 Quintal = 100 lb = 45.36 kg - could map to kg
            $kg = DB::table('units_of_measure')->where('abbreviation', 'kg')->first();
            if ($kg) {
                $unitMap['Quintal'] = $kg->id;
            }
        }

        if (! isset($unitMap['Rollo'])) {
            $pieza = DB::table('units_of_measure')->where('abbreviation', 'pz')->first();
            if ($pieza) {
                $unitMap['Rollo'] = $pieza->id;
            }
        }

        if (! isset($unitMap['Bloque'])) {
            $pieza = DB::table('units_of_measure')->where('abbreviation', 'pz')->first();
            if ($pieza) {
                $unitMap['Bloque'] = $pieza->id;
            }
        }

        return $unitMap;
    }
}
