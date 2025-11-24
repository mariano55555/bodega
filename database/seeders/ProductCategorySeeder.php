<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic product categories for El Salvador warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando categorías de productos salvadoreñas...');

        // Get companies
        $companies = DB::table('companies')->get()->keyBy('slug');

        $categories = [
            // Supermercados La Cadena - Retail categories
            [
                'company_slug' => 'supermercados-la-cadena',
                'categories' => [
                    ['name' => 'Lácteos y Derivados', 'code' => 'LC-LAC', 'description' => 'Leche, queso, yogurt, crema'],
                    ['name' => 'Carnes y Embutidos', 'code' => 'LC-CAR', 'description' => 'Carnes frescas, pollo, embutidos'],
                    ['name' => 'Frutas y Verduras', 'code' => 'LC-FRU', 'description' => 'Productos frescos de temporada'],
                    ['name' => 'Bebidas Alcohólicas', 'code' => 'LC-ALC', 'description' => 'Cervezas, licores, vinos'],
                    ['name' => 'Bebidas No Alcohólicas', 'code' => 'LC-BEB', 'description' => 'Refrescos, jugos, agua'],
                    ['name' => 'Productos de Limpieza', 'code' => 'LC-LIM', 'description' => 'Artículos de limpieza e higiene personal'],
                ],
            ],

            // Distribuidora Central SV - Distribution categories
            [
                'company_slug' => 'distribuidora-central-sv',
                'categories' => [
                    ['name' => 'Enlatados y Conservas', 'code' => 'DCS-ENL', 'description' => 'Productos enlatados y conservas'],
                    ['name' => 'Granos y Cereales', 'code' => 'DCS-GRA', 'description' => 'Arroz, frijoles, avena, cereales'],
                    ['name' => 'Aceites y Condimentos', 'code' => 'DCS-ACE', 'description' => 'Aceites, especias, salsas'],
                    ['name' => 'Medicamentos Genéricos', 'code' => 'DCS-MED', 'description' => 'Medicamentos de venta libre'],
                    ['name' => 'Vitaminas y Suplementos', 'code' => 'DCS-VIT', 'description' => 'Suplementos nutricionales'],
                ],
            ],

            // Grupo Industrial Salvadoreño - Manufacturing categories
            [
                'company_slug' => 'grupo-industrial-salvadoreno',
                'categories' => [
                    ['name' => 'Químicos Industriales', 'code' => 'GIS-QUI', 'description' => 'Productos químicos para manufactura'],
                    ['name' => 'Plásticos y Polímeros', 'code' => 'GIS-PLA', 'description' => 'Materiales plásticos'],
                    ['name' => 'Metales y Aleaciones', 'code' => 'GIS-MET', 'description' => 'Materiales metálicos'],
                    ['name' => 'Productos Plásticos', 'code' => 'GIS-PRO', 'description' => 'Envases, contenedores plásticos'],
                    ['name' => 'Productos Metálicos', 'code' => 'GIS-HER', 'description' => 'Herramientas, piezas metálicas'],
                ],
            ],

            // Comercial Centroamericana - Import/Export categories
            [
                'company_slug' => 'comercial-centroamericana',
                'categories' => [
                    ['name' => 'Electrónicos', 'code' => 'CCA-ELE', 'description' => 'Dispositivos electrónicos importados'],
                    ['name' => 'Textiles', 'code' => 'CCA-TEX', 'description' => 'Ropa y telas importadas'],
                    ['name' => 'Maquinaria', 'code' => 'CCA-MAQ', 'description' => 'Equipos y maquinaria industrial'],
                    ['name' => 'Café', 'code' => 'CCA-CAF', 'description' => 'Café salvadoreño de exportación'],
                    ['name' => 'Azúcar', 'code' => 'CCA-AZU', 'description' => 'Azúcar refinada para exportación'],
                ],
            ],

            // Agropecuaria San Miguel - Agricultural categories
            [
                'company_slug' => 'agropecuaria-san-miguel',
                'categories' => [
                    ['name' => 'Frutas Tropicales', 'code' => 'ASM-FRU', 'description' => 'Mango, papaya, piña, melón'],
                    ['name' => 'Vegetales', 'code' => 'ASM-VEG', 'description' => 'Tomate, cebolla, chile, lechuga'],
                    ['name' => 'Granos Básicos', 'code' => 'ASM-GRA', 'description' => 'Maíz, frijol, arroz'],
                    ['name' => 'Leche Fresca', 'code' => 'ASM-LEC', 'description' => 'Leche pasteurizada'],
                    ['name' => 'Quesos Artesanales', 'code' => 'ASM-QUE', 'description' => 'Quesos frescos y curados'],
                    ['name' => 'Crema y Mantequilla', 'code' => 'ASM-CRE', 'description' => 'Productos cremosos'],
                ],
            ],
        ];

        $totalCategories = 0;
        foreach ($categories as $categoryGroup) {
            $company = $companies->get($categoryGroup['company_slug']);
            if (! $company) {
                continue;
            }

            foreach ($categoryGroup['categories'] as $categoryData) {
                DB::table('product_categories')->insert([
                    'company_id' => $company->id,
                    'name' => $categoryData['name'],
                    'slug' => Str::slug($categoryData['name']),
                    'description' => $categoryData['description'],
                    'code' => $categoryData['code'],
                    'is_active' => true,
                    'active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->line("✓ {$categoryData['name']} - {$company->name}");
                $totalCategories++;
            }
        }

        $this->command->info('✓ Categorías de productos creadas exitosamente');
        $this->command->line("  - {$totalCategories} categorías especializadas");
        $this->command->line('  - Sectores: retail, distribución, manufactura, importación, agricultura');
    }
}
