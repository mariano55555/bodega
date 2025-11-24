<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic warehouses for El Salvador companies with capacity management.
     */
    public function run(): void
    {
        $this->command->info('Creando almacenes con gestión de capacidad...');

        // Get companies and branches and warehouse managers
        $companies = DB::table('companies')->orderBy('id')->get();
        $companyIdMap = [
            1 => $companies[0]->id ?? 1, // Supermercados La Cadena
            2 => $companies[1]->id ?? 2, // Distribuidora Central SV
            3 => $companies[2]->id ?? 3, // Grupo Industrial Salvadoreño
            4 => $companies[3]->id ?? 4, // Comercial Centroamericana
            5 => $companies[4]->id ?? 5, // Agropecuaria San Miguel
        ];

        $branches = DB::table('branches')->get()->keyBy('code');
        $warehouseManagers = DB::table('users')
            ->whereIn('email', [
                'almacen.central@lacadena.com.sv',
                'almacen.norte@dcentralsv.com',
                'almacen.materiaprima@gis.com.sv',
                'almacen.puerto@comercialca.com',
                'almacen.refrigerado@agrosanmiguel.com',
            ])
            ->get()
            ->keyBy('email');

        $warehouses = [
            // Supermercados La Cadena - Retail warehouses
            [
                'company_id' => 1,
                'branch_code' => 'LC-SS-001',
                'name' => 'Almacén Central de Distribución',
                'slug' => 'almacen-central-distribucion',
                'code' => 'LC-SS-ALM-001',
                'description' => 'Almacén principal para distribución a todas las sucursales del área metropolitana de San Salvador',
                'address' => 'Blvd. Constitución No. 1456, Bodega B, Zona Industrial',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'latitude' => 13.6929,
                'longitude' => -89.2182,
                'total_capacity' => 8500.00,
                'capacity_unit' => 'm3',
                'manager_email' => 'almacen.central@lacadena.com.sv',
                'operating_hours' => json_encode([
                    'monday' => '06:00-22:00',
                    'tuesday' => '06:00-22:00',
                    'wednesday' => '06:00-22:00',
                    'thursday' => '06:00-22:00',
                    'friday' => '06:00-22:00',
                    'saturday' => '06:00-20:00',
                    'sunday' => '08:00-18:00',
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'distribution',
                    'temperature_controlled' => true,
                    'security_level' => 'high',
                    'dock_doors' => 12,
                    'automation_level' => 'medium',
                    'operates_in_usd' => true,
                ]),
            ],
            [
                'company_id' => 1,
                'branch_code' => 'LC-SM-002',
                'name' => 'Almacén Regional San Miguel',
                'slug' => 'almacen-regional-san-miguel',
                'code' => 'LC-SM-ALM-001',
                'description' => 'Almacén regional para atender la zona oriental de El Salvador',
                'address' => 'Carretera Panamericana Km. 138, Colonia Industrial',
                'city' => 'San Miguel',
                'state' => 'San Miguel',
                'country' => 'SV',
                'postal_code' => '03101',
                'latitude' => 13.4835,
                'longitude' => -88.1773,
                'total_capacity' => 3200.00,
                'capacity_unit' => 'm3',
                'manager_email' => null,
                'operating_hours' => json_encode([
                    'monday' => '07:00-18:00',
                    'tuesday' => '07:00-18:00',
                    'wednesday' => '07:00-18:00',
                    'thursday' => '07:00-18:00',
                    'friday' => '07:00-18:00',
                    'saturday' => '07:00-15:00',
                    'sunday' => 'closed',
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'regional',
                    'dock_doors' => 6,
                    'automation_level' => 'low',
                    'serves_eastern_zone' => true,
                ]),
            ],

            // Distribuidora Central SV - Distribution warehouses
            [
                'company_id' => 2,
                'branch_code' => 'DCR-SM-001',
                'name' => 'Centro Logístico Santa Ana',
                'slug' => 'centro-logistico-santa-ana',
                'code' => 'DCR-SA-ALM-001',
                'description' => 'Centro logístico principal para distribución nacional y centroamericana',
                'address' => 'Km. 65 Carretera CA-1, Complejo Logístico Las Mercedes',
                'city' => 'Santa Ana',
                'state' => 'Santa Ana',
                'country' => 'SV',
                'postal_code' => '02101',
                'latitude' => 13.9942,
                'longitude' => -89.5597,
                'total_capacity' => 15000.00,
                'capacity_unit' => 'm3',
                'manager_email' => 'almacen.norte@dcentralsv.com',
                'operating_hours' => json_encode([
                    'operates_24_7' => true,
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'distribution_hub',
                    'operates_24_7' => true,
                    'dock_doors' => 24,
                    'automation_level' => 'high',
                    'cross_docking' => true,
                    'wms_system' => 'advanced',
                    'serves_ca4_region' => true,
                ]),
            ],
            [
                'company_id' => 2,
                'branch_code' => 'DCR-SS-002',
                'name' => 'Depósito Metropolitano Soyapango',
                'slug' => 'deposito-metropolitano-soyapango',
                'code' => 'DCR-SO-ALM-001',
                'description' => 'Depósito para atención del Área Metropolitana de San Salvador',
                'address' => 'Boulevard del Ejército Km. 5.5, Soyapango',
                'city' => 'Soyapango',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01120',
                'latitude' => 13.7179,
                'longitude' => -89.1419,
                'total_capacity' => 1800.00,
                'capacity_unit' => 'm3',
                'manager_email' => null,
                'operating_hours' => json_encode([
                    'monday' => '08:00-17:00',
                    'tuesday' => '08:00-17:00',
                    'wednesday' => '08:00-17:00',
                    'thursday' => '08:00-17:00',
                    'friday' => '08:00-17:00',
                    'saturday' => 'closed',
                    'sunday' => 'closed',
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'local_depot',
                    'dock_doors' => 4,
                    'automation_level' => 'low',
                    'amss_coverage' => true,
                ]),
            ],

            // Grupo Industrial Salvadoreño - Manufacturing warehouses
            [
                'company_id' => 3,
                'branch_code' => 'GIS-SS-001',
                'name' => 'Almacén de Materia Prima',
                'slug' => 'almacen-materia-prima',
                'code' => 'GID-HE-ALM-001',
                'description' => 'Almacén de materias primas para producción',
                'address' => 'Zona Industrial de Herrera, Nave A',
                'city' => 'Santo Domingo',
                'state' => 'Distrito Nacional',
                'country' => 'DO',
                'postal_code' => '10602',
                'latitude' => 18.4500,
                'longitude' => -69.9500,
                'total_capacity' => 4500.00,
                'capacity_unit' => 'm3',
                'manager_email' => 'almacen.materiaprima@gid.com.do',
                'operating_hours' => json_encode([
                    'operates_24_7' => true,
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'raw_materials',
                    'operates_24_7' => true,
                    'climate_controlled' => true,
                    'quality_control_area' => true,
                    'automation_level' => 'medium',
                ]),
            ],
            [
                'company_id' => 3,
                'branch_code' => 'GIS-SS-001',
                'name' => 'Almacén de Productos Terminados',
                'slug' => 'almacen-productos-terminados',
                'code' => 'GID-HE-ALM-002',
                'description' => 'Almacén de productos listos para expedición',
                'address' => 'Zona Industrial de Herrera, Nave B',
                'city' => 'Santo Domingo',
                'state' => 'Distrito Nacional',
                'country' => 'DO',
                'postal_code' => '10602',
                'latitude' => 18.4485,
                'longitude' => -69.9485,
                'total_capacity' => 6200.00,
                'capacity_unit' => 'm3',
                'manager_email' => null,
                'operating_hours' => json_encode([
                    'monday' => '06:00-22:00',
                    'tuesday' => '06:00-22:00',
                    'wednesday' => '06:00-22:00',
                    'thursday' => '06:00-22:00',
                    'friday' => '06:00-22:00',
                    'saturday' => '06:00-14:00',
                    'sunday' => 'closed',
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'finished_goods',
                    'dock_doors' => 8,
                    'packaging_area' => true,
                    'quality_control_area' => true,
                ]),
            ],

            // Comercial Centroamericana - Port warehouses
            [
                'company_id' => 4,
                'branch_code' => 'CC-AC-001',
                'name' => 'Almacén Portuario Caucedo',
                'slug' => 'almacen-portuario-caucedo',
                'code' => 'CC-CA-ALM-001',
                'description' => 'Almacén especializado en carga de importación y exportación',
                'address' => 'Puerto Multimodal Caucedo, Terminal A',
                'city' => 'Boca Chica',
                'state' => 'Santo Domingo',
                'country' => 'DO',
                'postal_code' => '15700',
                'latitude' => 18.4333,
                'longitude' => -69.6167,
                'total_capacity' => 12000.00,
                'capacity_unit' => 'm3',
                'manager_email' => 'almacen.puerto@comercialcaribe.com',
                'operating_hours' => json_encode([
                    'operates_24_7' => true,
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'port_terminal',
                    'operates_24_7' => true,
                    'customs_bonded' => true,
                    'container_handling' => true,
                    'crane_capacity' => '40_tons',
                    'security_level' => 'maximum',
                ]),
            ],
            [
                'company_id' => 4,
                'branch_code' => 'CC-AC-001',
                'name' => 'Depósito Fiscal Acajutla',
                'slug' => 'deposito-fiscal-caucedo',
                'code' => 'CC-CA-ALM-002',
                'description' => 'Depósito fiscal para mercancías en tránsito',
                'address' => 'Puerto Multimodal Caucedo, Terminal B',
                'city' => 'Boca Chica',
                'state' => 'Santo Domingo',
                'country' => 'DO',
                'postal_code' => '15700',
                'latitude' => 18.4315,
                'longitude' => -69.6145,
                'total_capacity' => 8800.00,
                'capacity_unit' => 'm3',
                'manager_email' => null,
                'operating_hours' => json_encode([
                    'operates_24_7' => true,
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'bonded_warehouse',
                    'operates_24_7' => true,
                    'customs_controlled' => true,
                    'temperature_zones' => ['ambient', 'refrigerated', 'frozen'],
                ]),
            ],

            // Agropecuaria San Miguel - Cold storage warehouses
            [
                'company_id' => 5,
                'branch_code' => 'ASM-SM-001',
                'name' => 'Cámara Frigorífica Central',
                'slug' => 'camara-frigorifica-central',
                'code' => 'AE-LR-ALM-001',
                'description' => 'Almacén refrigerado para productos perecederos',
                'address' => 'Carretera La Romana - Higüey Km. 15, Planta de Procesamiento',
                'city' => 'La Romana',
                'state' => 'La Romana',
                'country' => 'DO',
                'postal_code' => '22000',
                'latitude' => 18.4273,
                'longitude' => -68.9728,
                'total_capacity' => 2800.00,
                'capacity_unit' => 'm3',
                'manager_email' => 'almacen.refrigerado@agroestedr.com',
                'operating_hours' => json_encode([
                    'operates_24_7' => true,
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'cold_storage',
                    'operates_24_7' => true,
                    'temperature_zones' => [
                        'frozen' => '-18°C',
                        'chilled' => '2°C to 4°C',
                        'controlled' => '12°C to 15°C',
                    ],
                    'humidity_controlled' => true,
                    'organic_certified' => true,
                ]),
            ],
            [
                'company_id' => 5,
                'branch_code' => 'ASM-SM-001',
                'name' => 'Almacén de Empaque',
                'slug' => 'almacen-empaque',
                'code' => 'AE-LR-ALM-002',
                'description' => 'Área de empaque y preparación para exportación',
                'address' => 'Carretera La Romana - Higüey Km. 15, Área de Empaque',
                'city' => 'La Romana',
                'state' => 'La Romana',
                'country' => 'DO',
                'postal_code' => '22000',
                'latitude' => 18.4258,
                'longitude' => -68.9745,
                'total_capacity' => 1500.00,
                'capacity_unit' => 'm3',
                'manager_email' => null,
                'operating_hours' => json_encode([
                    'monday' => '05:00-21:00',
                    'tuesday' => '05:00-21:00',
                    'wednesday' => '05:00-21:00',
                    'thursday' => '05:00-21:00',
                    'friday' => '05:00-21:00',
                    'saturday' => '05:00-15:00',
                    'sunday' => 'closed',
                ]),
                'settings' => json_encode([
                    'warehouse_type' => 'packaging',
                    'packaging_lines' => 6,
                    'export_documentation' => true,
                    'quality_control' => true,
                ]),
            ],
        ];

        foreach ($warehouses as $warehouseData) {
            $branch = $branches->get($warehouseData['branch_code']);
            if (! $branch) {
                continue;
            }

            $manager = null;
            if ($warehouseData['manager_email']) {
                $manager = $warehouseManagers->get($warehouseData['manager_email']);
            }

            $warehouse = [
                'company_id' => $companyIdMap[$warehouseData['company_id']] ?? $warehouseData['company_id'],
                'branch_id' => $branch->id,
                'name' => $warehouseData['name'],
                'slug' => $warehouseData['slug'],
                'code' => $warehouseData['code'],
                'description' => $warehouseData['description'],
                'address' => $warehouseData['address'],
                'city' => $warehouseData['city'],
                'state' => $warehouseData['state'],
                'country' => $warehouseData['country'],
                'postal_code' => $warehouseData['postal_code'],
                'latitude' => $warehouseData['latitude'],
                'longitude' => $warehouseData['longitude'],
                'total_capacity' => $warehouseData['total_capacity'],
                'capacity_unit' => $warehouseData['capacity_unit'],
                'manager_id' => $manager ? $manager->id : null,
                'operating_hours' => $warehouseData['operating_hours'],
                'settings' => $warehouseData['settings'],
                'is_active' => true,
                'active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('warehouses')->insert($warehouse);

            $this->command->line("✓ {$warehouseData['name']} - {$warehouseData['city']} ({$warehouseData['total_capacity']} {$warehouseData['capacity_unit']})");
        }

        $this->command->info('✓ Almacenes creados exitosamente');
        $this->command->line('  - 11 almacenes especializados');
        $this->command->line('  - Tipos: distribución, manufactura, puerto, frigorífico, empaque');
        $this->command->line('  - Capacidad total: 64,300 m³');
        $this->command->line('  - Ubicaciones estratégicas en 4 ciudades principales');
    }
}
