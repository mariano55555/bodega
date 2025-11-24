# 🚀 Plan de Implementación - Demo ENA

## ✅ COMPLETADO

### 1. Migración de Base de Datos
- ✅ Archivo creado: `2025_11_20_150421_add_warehouse_type_and_hierarchy_to_warehouses_table.php`
- ✅ Campos agregados:
  - `warehouse_type` (string) - 'general' o 'fractional'
  - `parent_warehouse_id` (foreignId) - Jerarquía de bodegas
  - `level` (integer) - Nivel jerárquico (0=General, 1=Fraccionaria)
- ✅ Índices creados para performance

### 2. Seeder de Empresa
- ✅ Archivo creado: `ENACompanySeeder.php`
- ✅ Crea: Escuela Nacional de Agricultura
- ✅ Crea: Campus Central Santa Tecla

---

## 📋 PENDIENTE DE COMPLETAR

Los siguientes archivos fueron creados pero necesitan contenido. Te proporciono el código completo abajo para que lo copies:

### 3. ENAWarehousesSeeder.php
### 4. ENAUsersSeeder.php
### 5. ENAProductsSeeder.php
### 6. Actualizar DatabaseSeeder.php

---

## 📝 CONTENIDO COMPLETO DE LOS SEEDERS

### 📦 ENAWarehousesSeeder.php

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ENAWarehousesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏢 Creando bodegas de la ENA...');

        $company = DB::table('companies')->where('slug', 'escuela-nacional-agricultura')->first();
        $branch = DB::table('branches')->where('code', 'ENA-CC-001')->first();

        if (!$company || !$branch) {
            $this->command->error('❌ Error: Empresa o sucursal ENA no encontrada');
            return;
        }

        // 1. BODEGA GENERAL (Central)
        $bodegaCentralId = DB::table('warehouses')->insertGetId([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Almacén General ENA',
            'slug' => 'almacen-general-ena',
            'code' => 'ENA-BG-001',
            'warehouse_type' => 'general',
            'parent_warehouse_id' => null,
            'level' => 0,
            'description' => 'Bodega central que recibe todas las compras y donaciones. Distribuye a las bodegas fraccionarias según necesidad.',
            'address' => 'Edificio Administrativo, Campus Central',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'latitude' => 13.6773,
            'longitude' => -89.2797,
            'total_capacity' => 500.00,
            'capacity_unit' => 'm3',
            'manager_id' => null,
            'operating_hours' => json_encode([
                'monday' => '07:00-17:00',
                'tuesday' => '07:00-17:00',
                'wednesday' => '07:00-17:00',
                'thursday' => '07:00-17:00',
                'friday' => '07:00-17:00',
                'saturday' => '07:00-12:00',
                'sunday' => 'closed',
            ]),
            'settings' => json_encode([
                'warehouse_type' => 'general',
                'temperature_controlled' => false,
                'security_level' => 'high',
                'dock_doors' => 2,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Bodega General creada (ID: {$bodegaCentralId})");

        // 2. BODEGA FRACCIONARIA - Cultivos
        $bodegaCultivosId = DB::table('warehouses')->insertGetId([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Bodega Área de Cultivos',
            'slug' => 'bodega-area-cultivos',
            'code' => 'ENA-BF-CULTIVOS',
            'warehouse_type' => 'fractional',
            'parent_warehouse_id' => $bodegaCentralId,
            'level' => 1,
            'description' => 'Bodega fraccionaria que abastece las parcelas de cultivo y prácticas estudiantiles.',
            'address' => 'Parcelas de Cultivo, Campus ENA',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'latitude' => 13.6780,
            'longitude' => -89.2785,
            'total_capacity' => 80.00,
            'capacity_unit' => 'm3',
            'manager_id' => null,
            'operating_hours' => json_encode([
                'monday' => '07:00-17:00',
                'tuesday' => '07:00-17:00',
                'wednesday' => '07:00-17:00',
                'thursday' => '07:00-17:00',
                'friday' => '07:00-17:00',
                'saturday' => '07:00-12:00',
                'sunday' => 'closed',
            ]),
            'settings' => json_encode([
                'warehouse_type' => 'fractional',
                'area' => 'cultivos',
                'serves_students' => true,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Bodega Cultivos creada (ID: {$bodegaCultivosId})");

        // 3. BODEGA FRACCIONARIA - Pecuaria
        $bodegaPecuariaId = DB::table('warehouses')->insertGetId([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Bodega Unidad Pecuaria',
            'slug' => 'bodega-unidad-pecuaria',
            'code' => 'ENA-BF-PECUARIA',
            'warehouse_type' => 'fractional',
            'parent_warehouse_id' => $bodegaCentralId,
            'level' => 1,
            'description' => 'Bodega fraccionaria para la granja de ganado bovino, porcino y avícola.',
            'address' => 'Granja de Ganado, Campus ENA',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'latitude' => 13.6765,
            'longitude' => -89.2810,
            'total_capacity' => 60.00,
            'capacity_unit' => 'm3',
            'manager_id' => null,
            'operating_hours' => json_encode([
                'monday' => '06:00-18:00',
                'tuesday' => '06:00-18:00',
                'wednesday' => '06:00-18:00',
                'thursday' => '06:00-18:00',
                'friday' => '06:00-18:00',
                'saturday' => '06:00-12:00',
                'sunday' => '06:00-12:00',
            ]),
            'settings' => json_encode([
                'warehouse_type' => 'fractional',
                'area' => 'pecuaria',
                'temperature_controlled' => true,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Bodega Pecuaria creada (ID: {$bodegaPecuariaId})");

        // 4. BODEGA FRACCIONARIA - Procesamiento
        $bodegaProcesoId = DB::table('warehouses')->insertGetId([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Bodega Planta de Procesamiento',
            'slug' => 'bodega-planta-procesamiento',
            'code' => 'ENA-BF-PROCESO',
            'warehouse_type' => 'fractional',
            'parent_warehouse_id' => $bodegaCentralId,
            'level' => 1,
            'description' => 'Bodega fraccionaria para la planta agroindustrial. Almacena insumos y productos terminados.',
            'address' => 'Planta Agroindustrial, Campus ENA',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'latitude' => 13.6790,
            'longitude' => -89.2800,
            'total_capacity' => 50.00,
            'capacity_unit' => 'm3',
            'manager_id' => null,
            'operating_hours' => json_encode([
                'monday' => '07:00-16:00',
                'tuesday' => '07:00-16:00',
                'wednesday' => '07:00-16:00',
                'thursday' => '07:00-16:00',
                'friday' => '07:00-16:00',
                'saturday' => 'closed',
                'sunday' => 'closed',
            ]),
            'settings' => json_encode([
                'warehouse_type' => 'fractional',
                'area' => 'procesamiento',
                'has_finished_goods' => true,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Bodega Procesamiento creada (ID: {$bodegaProcesoId})");

        // 5. BODEGA FRACCIONARIA - Mantenimiento
        $bodegaMantId = DB::table('warehouses')->insertGetId([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Bodega Mantenimiento',
            'slug' => 'bodega-mantenimiento',
            'code' => 'ENA-BF-MANT',
            'warehouse_type' => 'fractional',
            'parent_warehouse_id' => $bodegaCentralId,
            'level' => 1,
            'description' => 'Bodega fraccionaria del taller de mantenimiento. Herramientas y repuestos.',
            'address' => 'Taller de Mantenimiento, Campus ENA',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'latitude' => 13.6770,
            'longitude' => -89.2790,
            'total_capacity' => 40.00,
            'capacity_unit' => 'm3',
            'manager_id' => null,
            'operating_hours' => json_encode([
                'monday' => '07:00-17:00',
                'tuesday' => '07:00-17:00',
                'wednesday' => '07:00-17:00',
                'thursday' => '07:00-17:00',
                'friday' => '07:00-17:00',
                'saturday' => '07:00-12:00',
                'sunday' => 'closed',
            ]),
            'settings' => json_encode([
                'warehouse_type' => 'fractional',
                'area' => 'mantenimiento',
                'has_tools' => true,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Bodega Mantenimiento creada (ID: {$bodegaMantId})");

        $this->command->info('✅ 5 bodegas ENA creadas exitosamente');
        $this->command->line('   - 1 Bodega General');
        $this->command->line('   - 4 Bodegas Fraccionarias');
    }
}
```

---

## 🔄 COMANDOS PARA EJECUTAR

```bash
# 1. Ejecutar migración
php artisan migrate

# 2. Limpiar base de datos (CUIDADO: Borra todo)
php artisan db:wipe

# 3. Ejecutar migraciones frescas
php artisan migrate:fresh

# 4. Ejecutar seeders de ENA
php artisan db:seed --class=ENACompanySeeder
php artisan db:seed --class=ENAWarehousesSeeder
php artisan db:seed --class=ENAUsersSeeder
php artisan db:seed --class=ENAProductsSeeder

# O ejecutar todo junto
php artisan migrate:fresh --seed
```

---

## 📌 PRÓXIMOS PASOS

1. ✅ Copiar el código de ENAWarehousesSeeder.php
2. ⏳ Crear ENAUsersSeeder.php (6 usuarios)
3. ⏳ Crear ENAProductsSeeder.php (85 productos en 5 categorías)
4. ⏳ Actualizar DatabaseSeeder.php para llamar solo estos seeders
5. ⏳ Ejecutar `php artisan migrate:fresh --seed`

---

**¿Quieres que continúe con los seeders de usuarios y productos?**
