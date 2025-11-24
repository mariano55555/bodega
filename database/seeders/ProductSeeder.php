<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic products for El Salvador warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando productos salvadoreños...');

        // Get companies, categories, and units of measure
        $companies = DB::table('companies')->get()->keyBy('slug');
        $categories = DB::table('product_categories')->get()->keyBy('slug');
        $units = DB::table('units_of_measure')->get()->keyBy('slug');

        $products = [
            // Supermercados La Cadena - Retail products
            [
                'company_slug' => 'supermercados-la-cadena',
                'category_slug' => 'lacteos-y-derivados',
                'products' => [
                    [
                        'name' => 'Leche Entera Pasteurizada La Canasta',
                        'description' => 'Leche entera pasteurizada de ganado salvadoreño, 1 litro en bolsa',
                        'sku' => 'LC-LEV-001',
                        'barcode' => '7501234567890',
                        'unit_slug' => 'litro',
                        'price' => 1.25,
                        'cost' => 0.85,
                        'min_stock' => 50,
                        'max_stock' => 500,
                    ],
                    [
                        'name' => 'Queso Fresco Salvadoreño El Buen Pastor',
                        'description' => 'Queso fresco artesanal salvadoreño elaborado tradicionalmente, 454g',
                        'sku' => 'LC-QUE-002',
                        'barcode' => '7501234567891',
                        'unit_slug' => 'kilogramo',
                        'price' => 4.50,
                        'cost' => 3.20,
                        'min_stock' => 20,
                        'max_stock' => 100,
                    ],
                    [
                        'name' => 'Yogurt Natural Salvadoreño Dos Pinos',
                        'description' => 'Yogurt natural cremoso sin azúcar añadida, producto nacional 1kg',
                        'sku' => 'LC-YOG-003',
                        'barcode' => '7501234567892',
                        'unit_slug' => 'kilogramo',
                        'price' => 3.75,
                        'cost' => 2.60,
                        'min_stock' => 30,
                        'max_stock' => 200,
                    ],
                ],
            ],
            [
                'company_slug' => 'supermercados-la-cadena',
                'category_slug' => 'bebidas-no-alcoholicas',
                'products' => [
                    [
                        'name' => 'Refresco Coca-Cola Familiar 2L',
                        'description' => 'Refresco de cola Coca-Cola presentación familiar de 2 litros',
                        'sku' => 'LC-COC-004',
                        'barcode' => '7501234567893',
                        'unit_slug' => 'litro',
                        'price' => 2.50,
                        'cost' => 1.75,
                        'min_stock' => 100,
                        'max_stock' => 1000,
                    ],
                    [
                        'name' => 'Agua Pura Cristal 500ml',
                        'description' => 'Agua purificada y embotellada Cristal, botella de 500ml',
                        'sku' => 'LC-AGU-005',
                        'barcode' => '7501234567894',
                        'unit_slug' => 'litro',
                        'price' => 0.75,
                        'cost' => 0.45,
                        'min_stock' => 200,
                        'max_stock' => 2000,
                    ],
                ],
            ],

            // Distribuidora Central SV - Distribution products
            [
                'company_slug' => 'distribuidora-central-sv',
                'category_slug' => 'granos-y-cereales',
                'products' => [
                    [
                        'name' => 'Arroz Blanco Primera Calidad',
                        'description' => 'Arroz blanco superior cultivado en El Salvador, saco de 50 kilogramos',
                        'sku' => 'DCS-ARR-001',
                        'barcode' => '7502234567890',
                        'unit_slug' => 'kilogramo',
                        'price' => 45.00,
                        'cost' => 32.00,
                        'min_stock' => 10,
                        'max_stock' => 100,
                    ],
                    [
                        'name' => 'Frijoles Rojos de Seda Salvadoreños',
                        'description' => 'Frijoles rojos de seda cosechados nacionalmente, saco de 46 kilogramos',
                        'sku' => 'DCS-FRI-002',
                        'barcode' => '7502234567891',
                        'unit_slug' => 'kilogramo',
                        'price' => 55.00,
                        'cost' => 40.00,
                        'min_stock' => 8,
                        'max_stock' => 80,
                    ],
                    [
                        'name' => 'Hojuelas de Avena Quaker',
                        'description' => 'Hojuelas de avena integral nutritiva, presentación de 800 gramos',
                        'sku' => 'DCS-AVE-003',
                        'barcode' => '7502234567892',
                        'unit_slug' => 'gramo',
                        'price' => 3.25,
                        'cost' => 2.40,
                        'min_stock' => 50,
                        'max_stock' => 300,
                    ],
                ],
            ],

            // Grupo Industrial Salvadoreño - Manufacturing products
            [
                'company_slug' => 'grupo-industrial-salvadoreno',
                'category_slug' => 'productos-plasticos',
                'products' => [
                    [
                        'name' => 'Envase Plástico Transparente 1L',
                        'description' => 'Botella plástica transparente de 1 litro para bebidas, fabricación nacional',
                        'sku' => 'GIS-ENV-001',
                        'barcode' => '7503234567890',
                        'unit_slug' => 'unidad',
                        'price' => 0.35,
                        'cost' => 0.22,
                        'min_stock' => 1000,
                        'max_stock' => 10000,
                    ],
                    [
                        'name' => 'Contenedor Industrial Plástico 20L',
                        'description' => 'Contenedor plástico industrial con tapa hermética de 20 litros',
                        'sku' => 'GIS-CON-002',
                        'barcode' => '7503234567891',
                        'unit_slug' => 'unidad',
                        'price' => 8.50,
                        'cost' => 5.75,
                        'min_stock' => 50,
                        'max_stock' => 500,
                    ],
                ],
            ],

            // Comercial Centroamericana - Import products
            [
                'company_slug' => 'comercial-centroamericana',
                'category_slug' => 'cafe',
                'products' => [
                    [
                        'name' => 'Café Gourmet 100% Salvadoreño',
                        'description' => 'Café arábica gourmet tostado y molido de las montañas de El Salvador, 340g',
                        'sku' => 'CCA-CAF-001',
                        'barcode' => '7504234567890',
                        'unit_slug' => 'gramo',
                        'price' => 8.75,
                        'cost' => 6.20,
                        'min_stock' => 100,
                        'max_stock' => 1000,
                    ],
                    [
                        'name' => 'Café Orgánico Apaneca Premium',
                        'description' => 'Café orgánico certificado en grano entero de Apaneca-Ilamatepec, 500g',
                        'sku' => 'CCA-CAF-002',
                        'barcode' => '7504234567891',
                        'unit_slug' => 'gramo',
                        'price' => 12.50,
                        'cost' => 8.90,
                        'min_stock' => 50,
                        'max_stock' => 500,
                    ],
                ],
            ],
            [
                'company_slug' => 'comercial-centroamericana',
                'category_slug' => 'azucar',
                'products' => [
                    [
                        'name' => 'Azúcar Blanca Refinada Central Izalco',
                        'description' => 'Azúcar blanca refinada del Ingenio Central Izalco, saco de 50 kilogramos',
                        'sku' => 'CCA-AZU-003',
                        'barcode' => '7504234567892',
                        'unit_slug' => 'kilogramo',
                        'price' => 32.00,
                        'cost' => 24.50,
                        'min_stock' => 20,
                        'max_stock' => 200,
                    ],
                ],
            ],

            // Agropecuaria San Miguel - Agricultural products
            [
                'company_slug' => 'agropecuaria-san-miguel',
                'category_slug' => 'frutas-tropicales',
                'products' => [
                    [
                        'name' => 'Mango Tommy Atkins Salvadoreño',
                        'description' => 'Mango Tommy Atkins de primera calidad cultivado en las tierras cálidas de El Salvador',
                        'sku' => 'ASM-MAN-001',
                        'barcode' => '7505234567890',
                        'unit_slug' => 'kilogramo',
                        'price' => 2.25,
                        'cost' => 1.50,
                        'min_stock' => 100,
                        'max_stock' => 500,
                    ],
                    [
                        'name' => 'Papaya Criolla Salvadoreña',
                        'description' => 'Papaya criolla mediana y dulce del oriente salvadoreño',
                        'sku' => 'ASM-PAP-002',
                        'barcode' => '7505234567891',
                        'unit_slug' => 'kilogramo',
                        'price' => 1.75,
                        'cost' => 1.20,
                        'min_stock' => 80,
                        'max_stock' => 400,
                    ],
                ],
            ],
            [
                'company_slug' => 'agropecuaria-san-miguel',
                'category_slug' => 'quesos-artesanales',
                'products' => [
                    [
                        'name' => 'Queso Duro Artesanal Salvadoreño',
                        'description' => 'Queso duro tradicional madurado artesanalmente por productores locales de San Miguel',
                        'sku' => 'ASM-QUE-003',
                        'barcode' => '7505234567892',
                        'unit_slug' => 'kilogramo',
                        'price' => 6.50,
                        'cost' => 4.80,
                        'min_stock' => 25,
                        'max_stock' => 150,
                    ],
                ],
            ],
        ];

        $totalProducts = 0;
        foreach ($products as $productGroup) {
            $company = $companies->get($productGroup['company_slug']);
            $category = $categories->get($productGroup['category_slug']);

            if (! $company || ! $category) {
                continue;
            }

            foreach ($productGroup['products'] as $productData) {
                $unit = $units->get($productData['unit_slug']);
                if (! $unit) {
                    continue;
                }

                DB::table('products')->insert([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name']),
                    'description' => $productData['description'],
                    'sku' => $productData['sku'],
                    'barcode' => $productData['barcode'],
                    'unit_of_measure_id' => $unit->id,
                    'unit_of_measure' => $unit->abbreviation,
                    'price' => $productData['price'],
                    'cost' => $productData['cost'],
                    'minimum_stock' => $productData['min_stock'],
                    'maximum_stock' => $productData['max_stock'],
                    'track_inventory' => true,
                    'valuation_method' => 'fifo',
                    'is_active' => true,
                    'active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->line("✓ {$productData['name']} - {$company->name}");
                $totalProducts++;
            }
        }

        $this->command->info('✓ Productos salvadoreños creados exitosamente');
        $this->command->line("  - {$totalProducts} productos en total");
        $this->command->line('  - Sectores: retail, distribución, manufactura, exportación, agricultura');
        $this->command->line('  - Productos típicos salvadoreños incluidos (café gourmet, quesos artesanales, frutas tropicales)');
        $this->command->line('  - Precios en dólares estadounidenses (USD) - moneda oficial de El Salvador');
    }
}
