<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic suppliers for El Salvador warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando proveedores salvadoreños...');

        // Get companies
        $companies = DB::table('companies')->get()->keyBy('slug');

        $suppliers = [
            // Suppliers for Supermercados La Cadena
            [
                'company_slugs' => ['supermercados-la-cadena'],
                'suppliers' => [
                    [
                        'name' => 'Lácteos La Canasta S.A.',
                        'legal_name' => 'Productos Lácteos La Canasta, S.A. de C.V.',
                        'tax_id' => '0614-080190-103-2',
                        'email' => 'ventas@lacanastalateos.com.sv',
                        'phone' => '+503-2345-6789',
                        'address' => 'Km. 12 Carretera a Santa Tecla',
                        'city' => 'Santa Tecla',
                        'state' => 'La Libertad',
                        'country' => 'SV',
                        'postal_code' => '05101',
                        'contact_person' => 'Ana María González',
                        'payment_terms' => '30 días',
                        'credit_limit' => 25000.00,
                        'category' => 'lácteos',
                    ],
                    [
                        'name' => 'Bebidas Cristal El Salvador',
                        'legal_name' => 'Embotelladora Cristal de El Salvador, S.A. de C.V.',
                        'tax_id' => '0614-150295-104-8',
                        'email' => 'distribución@cristalelsalvador.com',
                        'phone' => '+503-2234-5678',
                        'address' => 'Boulevard del Ejército No. 890',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Roberto Martínez',
                        'payment_terms' => '15 días',
                        'credit_limit' => 45000.00,
                        'category' => 'bebidas',
                    ],
                ],
            ],

            // Suppliers for Distribuidora Central SV
            [
                'company_slugs' => ['distribuidora-central-sv'],
                'suppliers' => [
                    [
                        'name' => 'Molinos Modernos S.A.',
                        'legal_name' => 'Molinos Modernos de El Salvador, S.A. de C.V.',
                        'tax_id' => '0614-220185-105-4',
                        'email' => 'ventas@molinosmodernos.com.sv',
                        'phone' => '+503-2567-8901',
                        'address' => 'Zona Industrial Los Almendros',
                        'city' => 'Mejicanos',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01118',
                        'contact_person' => 'Carlos Eduardo Rivas',
                        'payment_terms' => '45 días',
                        'credit_limit' => 75000.00,
                        'category' => 'granos_y_harinas',
                    ],
                    [
                        'name' => 'Farmacéutica Nacional',
                        'legal_name' => 'Laboratorios Farmacéuticos Nacionales, S.A. de C.V.',
                        'tax_id' => '0614-180398-106-7',
                        'email' => 'distribución@farmanacional.com.sv',
                        'phone' => '+503-2678-9012',
                        'address' => 'Calle Los Bambúes No. 234',
                        'city' => 'Antiguo Cuscatlán',
                        'state' => 'La Libertad',
                        'country' => 'SV',
                        'postal_code' => '05102',
                        'contact_person' => 'Dra. Patricia Hernández',
                        'payment_terms' => '60 días',
                        'credit_limit' => 35000.00,
                        'category' => 'farmacéuticos',
                    ],
                ],
            ],

            // Suppliers for Grupo Industrial Salvadoreño
            [
                'company_slugs' => ['grupo-industrial-salvadoreno'],
                'suppliers' => [
                    [
                        'name' => 'Química Industrial Centroamericana',
                        'legal_name' => 'Productos Químicos Industriales de Centroamérica, S.A. de C.V.',
                        'tax_id' => '0614-051088-107-1',
                        'email' => 'ventas@quimicacentroamericana.com',
                        'phone' => '+503-2789-0123',
                        'address' => 'Parque Industrial San Bartolo',
                        'city' => 'Ilopango',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01123',
                        'contact_person' => 'Ing. Fernando Castillo',
                        'payment_terms' => '30 días',
                        'credit_limit' => 125000.00,
                        'category' => 'químicos_industriales',
                    ],
                    [
                        'name' => 'Metales y Aleaciones SV',
                        'legal_name' => 'Distribuidora de Metales y Aleaciones de El Salvador, S.A. de C.V.',
                        'tax_id' => '0614-120792-108-9',
                        'email' => 'comercial@metalesaleaciones.com.sv',
                        'phone' => '+503-2890-1234',
                        'address' => 'Carretera al Puerto de La Libertad Km. 18',
                        'city' => 'La Libertad',
                        'state' => 'La Libertad',
                        'country' => 'SV',
                        'postal_code' => '05201',
                        'contact_person' => 'Miguel Ángel Portillo',
                        'payment_terms' => '45 días',
                        'credit_limit' => 95000.00,
                        'category' => 'metales_y_aleaciones',
                    ],
                ],
            ],

            // Suppliers for Comercial Centroamericana
            [
                'company_slugs' => ['comercial-centroamericana'],
                'suppliers' => [
                    [
                        'name' => 'Cooperativa de Caficultores de Apaneca',
                        'legal_name' => 'Cooperativa de Caficultores de la Cordillera de Apaneca, R.L.',
                        'tax_id' => '0614-051201-109-3',
                        'email' => 'exportaciones@caficultoresapaneca.coop',
                        'phone' => '+503-2901-2345',
                        'address' => 'Cantón El Carrizal, Apaneca',
                        'city' => 'Apaneca',
                        'state' => 'Ahuachapán',
                        'country' => 'SV',
                        'postal_code' => '08101',
                        'contact_person' => 'José Antonio Melgar',
                        'payment_terms' => '15 días',
                        'credit_limit' => 85000.00,
                        'category' => 'café_exportación',
                    ],
                    [
                        'name' => 'Ingenio Central Izalco',
                        'legal_name' => 'Compañía Azucarera Central Izalco, S.A. de C.V.',
                        'tax_id' => '0614-280575-110-7',
                        'email' => 'ventas@centralizalco.com.sv',
                        'phone' => '+503-2012-3456',
                        'address' => 'Cantón Izalco Norte',
                        'city' => 'Izalco',
                        'state' => 'Sonsonate',
                        'country' => 'SV',
                        'postal_code' => '06201',
                        'contact_person' => 'Ing. Ricardo Vilanova',
                        'payment_terms' => '30 días',
                        'credit_limit' => 65000.00,
                        'category' => 'azúcar_exportación',
                    ],
                ],
            ],

            // Suppliers for Agropecuaria San Miguel
            [
                'company_slugs' => ['agropecuaria-san-miguel'],
                'suppliers' => [
                    [
                        'name' => 'Finca San Rafael',
                        'legal_name' => 'Agropecuaria San Rafael de Oriente, S.A. de C.V.',
                        'tax_id' => '0614-151199-111-2',
                        'email' => 'produccion@fincasanrafael.com.sv',
                        'phone' => '+503-2123-4567',
                        'address' => 'Carretera a Jucuapa Km. 8',
                        'city' => 'Jucuapa',
                        'state' => 'Usulután',
                        'country' => 'SV',
                        'postal_code' => '07201',
                        'contact_person' => 'Ing. Agr. Claudia Ramos',
                        'payment_terms' => '21 días',
                        'credit_limit' => 40000.00,
                        'category' => 'frutas_y_vegetales',
                    ],
                    [
                        'name' => 'Ganadería El Porvenir',
                        'legal_name' => 'Empresa Ganadera El Porvenir de San Miguel, S.A. de C.V.',
                        'tax_id' => '0614-090693-112-8',
                        'email' => 'ventas@ganaderiaelporvenir.com.sv',
                        'phone' => '+503-2234-5678',
                        'address' => 'Cantón Las Flores, Ciudad Barrios',
                        'city' => 'Ciudad Barrios',
                        'state' => 'San Miguel',
                        'country' => 'SV',
                        'postal_code' => '03201',
                        'contact_person' => 'Dr. Veterinario Luis Morales',
                        'payment_terms' => '14 días',
                        'credit_limit' => 55000.00,
                        'category' => 'productos_lácteos',
                    ],
                ],
            ],

            // Shared suppliers (serve multiple companies)
            [
                'company_slugs' => ['supermercados-la-cadena', 'distribuidora-central-sv', 'agropecuaria-san-miguel'],
                'suppliers' => [
                    [
                        'name' => 'Transportes Salvadoreños Unidos',
                        'legal_name' => 'Empresa de Transportes Salvadoreños Unidos, S.A. de C.V.',
                        'tax_id' => '0614-070587-113-5',
                        'email' => 'servicios@transportesunidos.com.sv',
                        'phone' => '+503-2345-6789',
                        'address' => 'Boulevard Tutunichapa No. 456',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Lic. Martha Rodríguez',
                        'payment_terms' => '30 días',
                        'credit_limit' => 15000.00,
                        'category' => 'servicios_logísticos',
                    ],
                ],
            ],
        ];

        $totalSuppliers = 0;
        foreach ($suppliers as $supplierGroup) {
            foreach ($supplierGroup['suppliers'] as $supplierData) {
                foreach ($supplierGroup['company_slugs'] as $companySlug) {
                    $company = $companies->get($companySlug);
                    if (! $company) {
                        continue;
                    }

                    DB::table('suppliers')->insert([
                        'company_id' => $company->id,
                        'name' => $supplierData['name'],
                        'slug' => Str::slug($supplierData['name']),
                        'legal_name' => $supplierData['legal_name'],
                        'tax_id' => $supplierData['tax_id'],
                        'email' => $supplierData['email'],
                        'phone' => $supplierData['phone'],
                        'address' => $supplierData['address'],
                        'city' => $supplierData['city'],
                        'state' => $supplierData['state'],
                        'country' => $supplierData['country'],
                        'postal_code' => $supplierData['postal_code'],
                        'contact_person' => $supplierData['contact_person'],
                        'payment_terms' => $supplierData['payment_terms'],
                        'credit_limit' => $supplierData['credit_limit'],
                        'rating' => in_array($supplierData['category'], ['lácteos', 'café_exportación', 'azúcar_exportación']) ? 5 : 4,
                        'notes' => 'Categoría: '.$supplierData['category'].'. '.
                                  (in_array($supplierData['category'], ['farmacéuticos', 'químicos_industriales']) ? 'Requiere manejo especial.' : ''),
                        'is_active' => true,
                        'active_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->command->line("✓ {$supplierData['name']} - {$company->name}");
                }
                $totalSuppliers++;
            }
        }

        $this->command->info('✓ Proveedores salvadoreños creados exitosamente');
        $this->command->line("  - {$totalSuppliers} proveedores únicos");
        $this->command->line('  - Categorías: lácteos, bebidas, granos, farmacéuticos, químicos, metales, café, azúcar, agropecuarios, logística');
        $this->command->line('  - Ubicaciones distribuidas en todo El Salvador');
        $this->command->line('  - Términos de pago de 14 a 60 días según categoría');
    }
}
