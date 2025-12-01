<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ENACategoriesSeeder extends Seeder
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

        // Eliminar categorías existentes (soft delete)
        ProductCategory::where('company_id', $company->id)->delete();

        $this->command->info('Categorías existentes eliminadas.');

        // Crear categorías padre
        $parentCategories = [
            ['code' => 'CAT-54', 'legacy_code' => '54', 'name' => 'Adquisición de Bienes'],
            ['code' => 'CAT-61', 'legacy_code' => '61', 'name' => 'Activos'],
            ['code' => 'CAT-70', 'legacy_code' => '70', 'name' => 'Producción Interna'],
            ['code' => 'CAT-71', 'legacy_code' => '71', 'name' => 'Donaciones'],
            ['code' => 'CAT-72', 'legacy_code' => '72', 'name' => 'Convenios'],
            ['code' => 'CAT-73', 'legacy_code' => '73', 'name' => 'Autoconsumo'],
            ['code' => 'CAT-74', 'legacy_code' => '74', 'name' => 'Consignaciones'],
            ['code' => 'CAT-75', 'legacy_code' => '75', 'name' => 'Proyectos'],
            ['code' => 'CAT-76', 'legacy_code' => '76', 'name' => 'Permutas'],
            ['code' => 'CAT-77', 'legacy_code' => '77', 'name' => 'Reingresos a Bodega General'],
            ['code' => 'CAT-78', 'legacy_code' => '78', 'name' => 'Fondo de Mantenimiento de Estudiantes'],
            ['code' => 'CAT-80', 'legacy_code' => '80', 'name' => 'Entrada Producto Terminado'],
        ];

        $parentMap = [];

        foreach ($parentCategories as $data) {
            $category = ProductCategory::create([
                'company_id' => $company->id,
                'parent_id' => null,
                'name' => $data['name'],
                'code' => $data['code'],
                'legacy_code' => $data['legacy_code'],
                'is_active' => true,
                'active_at' => now(),
            ]);
            $parentMap[$data['legacy_code']] = $category->id;
        }

        $this->command->info('Categorías padre creadas: '.count($parentCategories));

        // Crear subcategorías
        $subcategories = [
            // 54 - Adquisición de Bienes
            ['legacy_code' => '54101', 'name' => 'Materiales e Insumos', 'parent' => '54'],
            ['legacy_code' => '54102', 'name' => 'Productos Agropecuarios', 'parent' => '54'],
            ['legacy_code' => '54103', 'name' => 'Productos Veterinarios', 'parent' => '54'],
            ['legacy_code' => '54104', 'name' => 'Productos Alimenticios para Personas', 'parent' => '54'],
            ['legacy_code' => '54105', 'name' => 'Productos de Papel y Cartón', 'parent' => '54'],
            ['legacy_code' => '54106', 'name' => 'Productos de Cuero y Caucho', 'parent' => '54'],
            ['legacy_code' => '54107', 'name' => 'Productos Químicos', 'parent' => '54'],
            ['legacy_code' => '54108', 'name' => 'Productos Farmacéuticos y Medicinales', 'parent' => '54'],
            ['legacy_code' => '54109', 'name' => 'Llantas y Neumáticos', 'parent' => '54'],
            ['legacy_code' => '54110', 'name' => 'Combustibles y Lubricantes', 'parent' => '54'],
            ['legacy_code' => '54111', 'name' => 'Minerales No Metálicos y Productos Derivados', 'parent' => '54'],
            ['legacy_code' => '54112', 'name' => 'Minerales Metálicos y Productos Derivados', 'parent' => '54'],
            ['legacy_code' => '54113', 'name' => 'Materiales e Instrumental de Lab y Uso Médico', 'parent' => '54'],
            ['legacy_code' => '54114', 'name' => 'Materiales de Oficina', 'parent' => '54'],
            ['legacy_code' => '54115', 'name' => 'Materiales Informáticos', 'parent' => '54'],
            ['legacy_code' => '54116', 'name' => 'Libros, Textos, Útiles de Enseñanza y Publicaciones', 'parent' => '54'],
            ['legacy_code' => '54117', 'name' => 'Materiales de Defensa y Seguridad Pública', 'parent' => '54'],
            ['legacy_code' => '54118', 'name' => 'Herramientas, Repuestos y Accesorios', 'parent' => '54'],
            ['legacy_code' => '54119', 'name' => 'Materiales Eléctricos', 'parent' => '54'],
            ['legacy_code' => '54199', 'name' => 'Bienes de Uso y Consumo Diversos', 'parent' => '54'],

            // 61 - Activos
            ['legacy_code' => '61101', 'name' => 'Mobiliario y Equipo de Oficina', 'parent' => '61'],
            ['legacy_code' => '61102', 'name' => 'Mobiliario y Equipo Educacional y Recreativo', 'parent' => '61'],
            ['legacy_code' => '61103', 'name' => 'Equipo de Computación', 'parent' => '61'],
            ['legacy_code' => '61104', 'name' => 'Equipo Médico y de Laboratorio', 'parent' => '61'],
            ['legacy_code' => '61105', 'name' => 'Equipo de Transporte, Tracción y Elevación', 'parent' => '61'],
            ['legacy_code' => '61106', 'name' => 'Equipo de Comunicación y Señalamiento', 'parent' => '61'],
            ['legacy_code' => '61107', 'name' => 'Equipo de Defensa y Seguridad', 'parent' => '61'],
            ['legacy_code' => '61108', 'name' => 'Maquinaria y Equipo de Producción', 'parent' => '61'],
            ['legacy_code' => '61109', 'name' => 'Equipo para Instalaciones', 'parent' => '61'],
            ['legacy_code' => '61199', 'name' => 'Bienes Muebles Diversos', 'parent' => '61'],

            // 70 - Producción Interna
            ['legacy_code' => '70101', 'name' => 'Producción Agrícola', 'parent' => '70'],
            ['legacy_code' => '70102', 'name' => 'Producción Pecuaria', 'parent' => '70'],
            ['legacy_code' => '70103', 'name' => 'Producción Agroindustrial', 'parent' => '70'],
            ['legacy_code' => '70199', 'name' => 'Otra Producción Interna', 'parent' => '70'],

            // 71 - Donaciones
            ['legacy_code' => '71101', 'name' => 'Donaciones de Materiales e Insumos', 'parent' => '71'],
            ['legacy_code' => '71102', 'name' => 'Donaciones de Equipo', 'parent' => '71'],
            ['legacy_code' => '71199', 'name' => 'Otras Donaciones', 'parent' => '71'],

            // 72 - Convenios
            ['legacy_code' => '72101', 'name' => 'Bienes por Convenio Institucional', 'parent' => '72'],
            ['legacy_code' => '72199', 'name' => 'Otros Bienes por Convenio', 'parent' => '72'],

            // 73 - Autoconsumo
            ['legacy_code' => '73101', 'name' => 'Autoconsumo de Producción Agrícola', 'parent' => '73'],
            ['legacy_code' => '73102', 'name' => 'Autoconsumo de Producción Pecuaria', 'parent' => '73'],
            ['legacy_code' => '73199', 'name' => 'Otro Autoconsumo', 'parent' => '73'],

            // 74 - Consignaciones
            ['legacy_code' => '74101', 'name' => 'Bienes en Consignación', 'parent' => '74'],
            ['legacy_code' => '74199', 'name' => 'Otras Consignaciones', 'parent' => '74'],

            // 75 - Proyectos
            ['legacy_code' => '75101', 'name' => 'Materiales para Proyectos', 'parent' => '75'],
            ['legacy_code' => '75102', 'name' => 'Equipos para Proyectos', 'parent' => '75'],
            ['legacy_code' => '75199', 'name' => 'Otros Bienes para Proyectos', 'parent' => '75'],

            // 76 - Permutas
            ['legacy_code' => '76101', 'name' => 'Bienes Recibidos por Permuta', 'parent' => '76'],
            ['legacy_code' => '76199', 'name' => 'Otras Permutas', 'parent' => '76'],

            // 77 - Reingresos a Bodega General
            ['legacy_code' => '77101', 'name' => 'Reingreso de Materiales', 'parent' => '77'],
            ['legacy_code' => '77102', 'name' => 'Reingreso de Equipos', 'parent' => '77'],
            ['legacy_code' => '77199', 'name' => 'Otros Reingresos', 'parent' => '77'],

            // 78 - Fondo de Mantenimiento de Estudiantes
            ['legacy_code' => '78101', 'name' => 'Materiales Fondo Estudiantes', 'parent' => '78'],
            ['legacy_code' => '78199', 'name' => 'Otros Fondo Estudiantes', 'parent' => '78'],

            // 80 - Entrada Producto Terminado
            ['legacy_code' => '80101', 'name' => 'Producto Terminado Agrícola', 'parent' => '80'],
            ['legacy_code' => '80102', 'name' => 'Producto Terminado Pecuario', 'parent' => '80'],
            ['legacy_code' => '80103', 'name' => 'Producto Terminado Agroindustrial', 'parent' => '80'],
            ['legacy_code' => '80199', 'name' => 'Otro Producto Terminado', 'parent' => '80'],
        ];

        $subcategoryCount = 0;
        foreach ($subcategories as $data) {
            if (! isset($parentMap[$data['parent']])) {
                $this->command->warn("Categoría padre no encontrada: {$data['parent']}");

                continue;
            }

            ProductCategory::create([
                'company_id' => $company->id,
                'parent_id' => $parentMap[$data['parent']],
                'name' => $data['name'],
                'code' => 'SUB-'.$data['legacy_code'],
                'legacy_code' => $data['legacy_code'],
                'is_active' => true,
                'active_at' => now(),
            ]);
            $subcategoryCount++;
        }

        $this->command->info("Subcategorías creadas: {$subcategoryCount}");
        $this->command->info('Seeder ENACategoriesSeeder completado exitosamente.');
    }
}
