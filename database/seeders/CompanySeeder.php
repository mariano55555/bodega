<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic El Salvador companies for the warehouse management system.
     */
    public function run(): void
    {
        $this->command->info('Creando empresas salvadoreñas...');

        $companies = [
            [
                'name' => 'Supermercados La Cadena',
                'slug' => 'supermercados-la-cadena',
                'legal_name' => 'Supermercados La Cadena, S.A. de C.V.',
                'tax_id' => '0614-050885-101-5',
                'email' => 'info@lacadena.com.sv',
                'phone' => '+503-2555-0100',
                'website' => 'https://www.lacadena.com.sv',
                'address' => 'Blvd. de Los Héroes No. 1456',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'default_currency' => 'USD',
                'timezone' => 'America/El_Salvador',
                'settings' => json_encode([
                    'business_type' => 'retail',
                    'industry' => 'supermercados',
                    'employees' => 2500,
                    'founded_year' => 1985,
                    'locations' => 35,
                    'warehouse_capacity' => 'large',
                ]),
                'is_active' => true,
                'active_at' => now(),
            ],
            [
                'name' => 'Distribuidora Central SV',
                'slug' => 'distribuidora-central-sv',
                'legal_name' => 'Distribuidora Central de El Salvador, S.A. de C.V.',
                'tax_id' => '0614-120990-102-6',
                'email' => 'contacto@dcentralsv.com',
                'phone' => '+503-2555-0200',
                'website' => 'https://www.dcentralsv.com',
                'address' => 'Km. 15 Carretera Panamericana',
                'city' => 'Santa Ana',
                'state' => 'Santa Ana',
                'country' => 'SV',
                'postal_code' => '02101',
                'default_currency' => 'USD',
                'timezone' => 'America/El_Salvador',
                'settings' => json_encode([
                    'business_type' => 'distribution',
                    'industry' => 'distribución',
                    'employees' => 850,
                    'founded_year' => 1992,
                    'coverage_area' => 'nacional',
                    'specialty' => 'productos_consumo_masivo',
                ]),
                'is_active' => true,
                'active_at' => now(),
            ],
            [
                'name' => 'Grupo Industrial Salvadoreño',
                'slug' => 'grupo-industrial-salvadoreno',
                'legal_name' => 'Grupo Industrial Salvadoreño, S.A. de C.V.',
                'tax_id' => '0614-280778-103-7',
                'email' => 'info@gis.com.sv',
                'phone' => '+503-2555-0300',
                'website' => 'https://www.gis.com.sv',
                'address' => 'Zona Industrial de Soyapango',
                'city' => 'Soyapango',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01120',
                'default_currency' => 'USD',
                'timezone' => 'America/El_Salvador',
                'settings' => json_encode([
                    'business_type' => 'manufacturing',
                    'industry' => 'manufactura',
                    'employees' => 1200,
                    'founded_year' => 1978,
                    'production_capacity' => 'high',
                    'export_markets' => ['guatemala', 'honduras', 'costa_rica', 'usa'],
                ]),
                'is_active' => true,
                'active_at' => now(),
            ],
            [
                'name' => 'Comercial Centroamericana',
                'slug' => 'comercial-centroamericana',
                'legal_name' => 'Comercial Centroamericana Import Export, S.A. de C.V.',
                'tax_id' => '0614-010201-104-8',
                'email' => 'ventas@comercialca.com',
                'phone' => '+503-2555-0400',
                'website' => 'https://www.comercialca.com',
                'address' => 'Puerto de Acajutla, Zona Portuaria',
                'city' => 'Acajutla',
                'state' => 'Sonsonate',
                'country' => 'SV',
                'postal_code' => '06101',
                'default_currency' => 'USD',
                'timezone' => 'America/El_Salvador',
                'settings' => json_encode([
                    'business_type' => 'import_export',
                    'industry' => 'comercio_internacional',
                    'employees' => 320,
                    'founded_year' => 2001,
                    'main_ports' => ['acajutla', 'la_union'],
                    'trade_routes' => ['usa', 'europa', 'asia', 'centroamerica'],
                ]),
                'is_active' => true,
                'active_at' => now(),
            ],
            [
                'name' => 'Agropecuaria San Miguel',
                'slug' => 'agropecuaria-san-miguel',
                'legal_name' => 'Agropecuaria San Miguel, S.A. de C.V.',
                'tax_id' => '0614-151195-105-9',
                'email' => 'info@agrosanmiguel.com',
                'phone' => '+503-2555-0500',
                'website' => 'https://www.agrosanmiguel.com',
                'address' => 'Carretera Panamericana Km. 125',
                'city' => 'San Miguel',
                'state' => 'San Miguel',
                'country' => 'SV',
                'postal_code' => '03101',
                'default_currency' => 'USD',
                'timezone' => 'America/El_Salvador',
                'settings' => json_encode([
                    'business_type' => 'agribusiness',
                    'industry' => 'agropecuaria',
                    'employees' => 450,
                    'founded_year' => 1995,
                    'main_products' => ['frutas', 'vegetales', 'productos_lacteos'],
                    'cold_storage' => true,
                ]),
                'is_active' => true,
                'active_at' => now(),
            ],
        ];

        foreach ($companies as $company) {
            $company['created_at'] = now();
            $company['updated_at'] = now();

            DB::table('companies')->insert($company);

            $this->command->line("✓ {$company['name']} - {$company['city']}");
        }

        $this->command->info('✓ Empresas salvadoreñas creadas exitosamente');
        $this->command->line('  - 5 empresas principales');
        $this->command->line('  - Sectores: retail, distribución, manufactura, comercio internacional, agropecuaria');
        $this->command->line('  - Ubicaciones: San Salvador, Santa Ana, Soyapango, Acajutla, San Miguel');
    }
}
