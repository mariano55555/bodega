<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic branches for El Salvador companies in major cities.
     */
    public function run(): void
    {
        $this->command->info('Creando sucursales en las principales ciudades de El Salvador...');

        // Get companies
        $companies = DB::table('companies')->get()->keyBy('slug');

        // Get branch managers by email
        $branchManagers = DB::table('users')
            ->whereIn('email', [
                'manager.sansalvador@lacadena.com.sv',
                'manager.santaana@dcentralsv.com',
                'manager.produccion@gis.com.sv',
                'manager.acajutla@comercialca.com',
                'manager.sanmiguel@agrosanmiguel.com',
            ])
            ->get()
            ->keyBy('email');

        $branches = [
            // Supermercados La Cadena - Retail chain with multiple locations
            [
                'company_slug' => 'supermercados-la-cadena',
                'name' => 'Oficina Central San Salvador',
                'slug' => 'oficina-central-san-salvador',
                'code' => 'LC-SS-001',
                'description' => 'Oficina principal y centro de distribución en la capital',
                'address' => 'Blvd. de Los Héroes No. 1456',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'manager_email' => 'manager.sansalvador@lacadena.com.sv',
                'settings' => json_encode([
                    'branch_type' => 'headquarters',
                    'has_distribution_center' => true,
                    'operating_hours' => [
                        'monday' => '8:00-18:00',
                        'tuesday' => '8:00-18:00',
                        'wednesday' => '8:00-18:00',
                        'thursday' => '8:00-18:00',
                        'friday' => '8:00-18:00',
                        'saturday' => '8:00-12:00',
                        'sunday' => 'closed',
                    ],
                ]),
            ],
            [
                'company_slug' => 'supermercados-la-cadena',
                'name' => 'Sucursal Santa Ana',
                'slug' => 'sucursal-santa-ana',
                'code' => 'LC-SA-002',
                'description' => 'Centro regional para el occidente',
                'address' => 'Av. Independencia Sur No. 89',
                'city' => 'Santa Ana',
                'state' => 'Santa Ana',
                'country' => 'SV',
                'postal_code' => '02101',
                'manager_email' => null,
                'settings' => json_encode([
                    'branch_type' => 'regional',
                    'coverage_area' => ['santa_ana', 'ahuachapan', 'sonsonate'],
                    'has_retail_store' => true,
                ]),
            ],
            [
                'company_slug' => 'supermercados-la-cadena',
                'name' => 'Sucursal San Miguel',
                'slug' => 'sucursal-san-miguel',
                'code' => 'LC-SM-003',
                'description' => 'Sucursal del Oriente',
                'address' => 'Av. Roosevelt Sur No. 25',
                'city' => 'San Miguel',
                'state' => 'San Miguel',
                'country' => 'SV',
                'postal_code' => '03101',
                'manager_email' => null,
                'settings' => json_encode([
                    'branch_type' => 'retail',
                    'tourist_area' => true,
                ]),
            ],

            // Distribuidora Central SV - Distribution network
            [
                'company_slug' => 'distribuidora-central-sv',
                'name' => 'Centro de Distribución Santa Ana',
                'slug' => 'centro-distribucion-santa-ana',
                'code' => 'DCS-SA-001',
                'description' => 'Centro principal de distribución para el occidente del país',
                'address' => 'Km. 15 Carretera Panamericana',
                'city' => 'Santa Ana',
                'state' => 'Santa Ana',
                'country' => 'SV',
                'postal_code' => '02101',
                'manager_email' => 'manager.santaana@dcentralsv.com',
                'settings' => json_encode([
                    'branch_type' => 'distribution_center',
                    'coverage_area' => ['occidente', 'norte'],
                    'capacity' => 'large',
                    'operates_24_7' => true,
                ]),
            ],
            [
                'company_slug' => 'distribuidora-central-sv',
                'name' => 'Oficina San Salvador',
                'slug' => 'oficina-san-salvador',
                'code' => 'DCS-SS-002',
                'description' => 'Oficina administrativa y ventas para el Área Metropolitana',
                'address' => 'Centro Comercial Metrocentro, Local 15',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'manager_email' => null,
                'settings' => json_encode([
                    'branch_type' => 'sales_office',
                    'has_showroom' => true,
                ]),
            ],

            // Grupo Industrial Salvadoreño - Manufacturing facilities
            [
                'company_slug' => 'grupo-industrial-salvadoreno',
                'name' => 'Planta de Producción Soyapango',
                'slug' => 'planta-produccion-soyapango',
                'code' => 'GIS-SO-001',
                'description' => 'Planta principal de manufactura',
                'address' => 'Zona Industrial de Soyapango',
                'city' => 'Soyapango',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01120',
                'manager_email' => 'manager.produccion@gis.com.sv',
                'settings' => json_encode([
                    'branch_type' => 'manufacturing',
                    'production_lines' => 8,
                    'operates_24_7' => true,
                    'certified_iso' => true,
                ]),
            ],
            [
                'company_slug' => 'grupo-industrial-salvadoreno',
                'name' => 'Centro de Investigación y Desarrollo',
                'slug' => 'centro-investigacion-desarrollo',
                'code' => 'GIS-RD-002',
                'description' => 'Centro de I+D y laboratorios',
                'address' => 'Blvd. Constitución No. 92',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'manager_email' => null,
                'settings' => json_encode([
                    'branch_type' => 'research_development',
                    'has_laboratory' => true,
                    'quality_control' => true,
                ]),
            ],

            // Comercial Centroamericana - Import/Export facilities
            [
                'company_slug' => 'comercial-centroamericana',
                'name' => 'Terminal Portuaria Acajutla',
                'slug' => 'terminal-portuaria-acajutla',
                'code' => 'CCA-AC-001',
                'description' => 'Terminal principal de importación y exportación',
                'address' => 'Puerto de Acajutla, Zona Portuaria',
                'city' => 'Acajutla',
                'state' => 'Sonsonate',
                'country' => 'SV',
                'postal_code' => '06101',
                'manager_email' => 'manager.acajutla@comercialca.com',
                'settings' => json_encode([
                    'branch_type' => 'port_terminal',
                    'operates_24_7' => true,
                    'customs_bonded' => true,
                    'container_capacity' => 5000,
                ]),
            ],
            [
                'company_slug' => 'comercial-centroamericana',
                'name' => 'Oficina La Unión',
                'slug' => 'oficina-la-union',
                'code' => 'CCA-LU-002',
                'description' => 'Oficina regional oriente y puerto secundario',
                'address' => 'Av. General Cabrera No. 45',
                'city' => 'La Unión',
                'state' => 'La Unión',
                'country' => 'SV',
                'postal_code' => '04101',
                'manager_email' => null,
                'settings' => json_encode([
                    'branch_type' => 'regional_office',
                    'port_access' => true,
                    'fishing_operations' => true,
                ]),
            ],

            // Agropecuaria San Miguel - Agricultural operations
            [
                'company_slug' => 'agropecuaria-san-miguel',
                'name' => 'Centro de Procesamiento San Miguel',
                'slug' => 'centro-procesamiento-san-miguel',
                'code' => 'ASM-SM-001',
                'description' => 'Centro principal de procesamiento y empaque',
                'address' => 'Carretera Panamericana Km. 125',
                'city' => 'San Miguel',
                'state' => 'San Miguel',
                'country' => 'SV',
                'postal_code' => '03101',
                'manager_email' => 'manager.sanmiguel@agrosanmiguel.com',
                'settings' => json_encode([
                    'branch_type' => 'processing_center',
                    'cold_storage' => true,
                    'organic_certified' => true,
                    'export_facility' => true,
                ]),
            ],
            [
                'company_slug' => 'agropecuaria-san-miguel',
                'name' => 'Finca El Progreso',
                'slug' => 'finca-el-progreso',
                'code' => 'ASM-EP-002',
                'description' => 'Finca principal de producción agrícola',
                'address' => 'Km. 8 Carretera San Miguel-Usulután',
                'city' => 'Usulután',
                'state' => 'Usulután',
                'country' => 'SV',
                'postal_code' => '07101',
                'manager_email' => null,
                'settings' => json_encode([
                    'branch_type' => 'farm',
                    'hectares' => 850,
                    'irrigation_system' => 'drip',
                    'greenhouse_area' => 120,
                ]),
            ],
        ];

        foreach ($branches as $branchData) {
            $company = $companies->get($branchData['company_slug']);
            if (! $company) {
                continue;
            }

            $manager = null;
            if ($branchData['manager_email']) {
                $manager = $branchManagers->get($branchData['manager_email']);
            }

            $branch = [
                'company_id' => $company->id,
                'name' => $branchData['name'],
                'slug' => $branchData['slug'],
                'code' => $branchData['code'],
                'description' => $branchData['description'],
                'address' => $branchData['address'],
                'city' => $branchData['city'],
                'state' => $branchData['state'],
                'country' => $branchData['country'],
                'postal_code' => $branchData['postal_code'],
                'manager_id' => $manager ? $manager->id : null,
                'settings' => $branchData['settings'],
                'is_active' => true,
                'active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('branches')->insert($branch);

            $this->command->line("✓ {$branchData['name']} - {$branchData['city']}");
        }

        $this->command->info('✓ Sucursales creadas exitosamente');
        $this->command->line('  - 11 sucursales en total');
        $this->command->line('  - Tipos: oficinas centrales, centros de distribución, plantas, puertos, fincas');
        $this->command->line('  - Ciudades: San Salvador, Santa Ana, Soyapango, Acajutla, San Miguel, Usulután');
    }
}
