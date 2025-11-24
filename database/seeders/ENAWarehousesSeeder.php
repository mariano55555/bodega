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

        if (! $company || ! $branch) {
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

        // 2-5. BODEGAS FRACCIONARIAS
        $fraccionarias = [
            [
                'name' => 'Bodega Área de Cultivos',
                'slug' => 'bodega-area-cultivos',
                'code' => 'ENA-BF-CULTIVOS',
                'description' => 'Bodega fraccionaria que abastece las parcelas de cultivo y prácticas estudiantiles.',
                'address' => 'Parcelas de Cultivo, Campus ENA',
                'latitude' => 13.6780,
                'longitude' => -89.2785,
                'capacity' => 80.00,
                'area' => 'cultivos',
            ],
            [
                'name' => 'Bodega Unidad Pecuaria',
                'slug' => 'bodega-unidad-pecuaria',
                'code' => 'ENA-BF-PECUARIA',
                'description' => 'Bodega fraccionaria para la granja de ganado bovino, porcino y avícola.',
                'address' => 'Granja de Ganado, Campus ENA',
                'latitude' => 13.6765,
                'longitude' => -89.2810,
                'capacity' => 60.00,
                'area' => 'pecuaria',
            ],
            [
                'name' => 'Bodega Planta de Procesamiento',
                'slug' => 'bodega-planta-procesamiento',
                'code' => 'ENA-BF-PROCESO',
                'description' => 'Bodega fraccionaria para la planta agroindustrial. Almacena insumos y productos terminados.',
                'address' => 'Planta Agroindustrial, Campus ENA',
                'latitude' => 13.6790,
                'longitude' => -89.2800,
                'capacity' => 50.00,
                'area' => 'procesamiento',
            ],
            [
                'name' => 'Bodega Mantenimiento',
                'slug' => 'bodega-mantenimiento',
                'code' => 'ENA-BF-MANT',
                'description' => 'Bodega fraccionaria del taller de mantenimiento. Herramientas y repuestos.',
                'address' => 'Taller de Mantenimiento, Campus ENA',
                'latitude' => 13.6770,
                'longitude' => -89.2790,
                'capacity' => 40.00,
                'area' => 'mantenimiento',
            ],
        ];

        foreach ($fraccionarias as $bodega) {
            DB::table('warehouses')->insert([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $bodega['name'],
                'slug' => $bodega['slug'],
                'code' => $bodega['code'],
                'warehouse_type' => 'fractional',
                'parent_warehouse_id' => $bodegaCentralId,
                'level' => 1,
                'description' => $bodega['description'],
                'address' => $bodega['address'],
                'city' => 'Santa Tecla',
                'state' => 'La Libertad',
                'country' => 'SV',
                'postal_code' => '01101',
                'latitude' => $bodega['latitude'],
                'longitude' => $bodega['longitude'],
                'total_capacity' => $bodega['capacity'],
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
                    'area' => $bodega['area'],
                ]),
                'is_active' => true,
                'active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->line("✓ {$bodega['name']} creada");
        }

        $this->command->info('✅ 5 bodegas ENA creadas exitosamente');
        $this->command->line('   - 1 Bodega General');
        $this->command->line('   - 4 Bodegas Fraccionarias');
    }
}
