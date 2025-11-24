<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic customers for El Salvador warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando clientes salvadoreños...');

        // Get companies
        $companies = DB::table('companies')->get()->keyBy('slug');

        $customers = [
            // Customers for Supermercados La Cadena (B2C mostly)
            [
                'company_slug' => 'supermercados-la-cadena',
                'customers' => [
                    [
                        'name' => 'Restaurant El Típico Salvadoreño',
                        'legal_name' => 'Restaurantes El Típico Salvadoreño, S.A. de C.V.',
                        'tax_id' => '0614-120598-201-4',
                        'email' => 'compras@eltipicosalvadoreno.com',
                        'phone' => '+503-2456-7890',
                        'address' => 'Colonia Escalón, Paseo General Escalón No. 123',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Chef Carlos Monterrosa',
                        'customer_type' => 'business',
                        'credit_limit' => 5000.00,
                        'payment_terms' => '15 días',
                    ],
                    [
                        'name' => 'Hotel Plaza Real',
                        'legal_name' => 'Hotelera Plaza Real de El Salvador, S.A. de C.V.',
                        'tax_id' => '0614-050401-202-1',
                        'email' => 'administracion@hotelplazareal.com.sv',
                        'phone' => '+503-2567-8901',
                        'address' => 'Avenida La Revolución No. 456',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Lic. María Elena Castillo',
                        'customer_type' => 'business',
                        'credit_limit' => 8000.00,
                        'payment_terms' => '30 días',
                    ],
                    [
                        'name' => 'Cafetería Universitaria USAL',
                        'legal_name' => 'Universidad de El Salvador - Cafetería Central',
                        'tax_id' => '0614-010150-203-8',
                        'email' => 'cafeteria@ues.edu.sv',
                        'phone' => '+503-2678-9012',
                        'address' => 'Ciudad Universitaria, Final 25 Av. Norte',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Lic. Roberto Mejía',
                        'customer_type' => 'institutional',
                        'credit_limit' => 12000.00,
                        'payment_terms' => '45 días',
                    ],
                ],
            ],

            // Customers for Distribuidora Central SV (B2B distribution)
            [
                'company_slug' => 'distribuidora-central-sv',
                'customers' => [
                    [
                        'name' => 'Tienda San José',
                        'legal_name' => 'Comercializadora San José de Santa Ana, S.A. de C.V.',
                        'tax_id' => '0614-181299-204-5',
                        'email' => 'gerencia@tiendasanjose.com.sv',
                        'phone' => '+503-2789-0123',
                        'address' => 'Av. José Matías Delgado No. 789',
                        'city' => 'Santa Ana',
                        'state' => 'Santa Ana',
                        'country' => 'SV',
                        'postal_code' => '02101',
                        'contact_person' => 'Sr. José Antonio Flores',
                        'customer_type' => 'retailer',
                        'credit_limit' => 15000.00,
                        'payment_terms' => '30 días',
                    ],
                    [
                        'name' => 'Farmacia El Buen Samaritano',
                        'legal_name' => 'Farmacia El Buen Samaritano, S.A. de C.V.',
                        'tax_id' => '0614-220703-205-2',
                        'email' => 'compras@farmaciabuensamaritano.com',
                        'phone' => '+503-2890-1234',
                        'address' => 'Calle Arce No. 234, Centro Histórico',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Dr. Farmacéutico Luis Hernández',
                        'customer_type' => 'healthcare',
                        'credit_limit' => 10000.00,
                        'payment_terms' => '21 días',
                    ],
                    [
                        'name' => 'Minisuper La Bendición',
                        'legal_name' => 'Minisuper La Bendición de San Miguel, S.A. de C.V.',
                        'tax_id' => '0614-090805-206-9',
                        'email' => 'propietario@minisuperlabendicion.com',
                        'phone' => '+503-2901-2345',
                        'address' => 'Barrio San Francisco, 3a Calle Poniente No. 567',
                        'city' => 'San Miguel',
                        'state' => 'San Miguel',
                        'country' => 'SV',
                        'postal_code' => '03101',
                        'contact_person' => 'Sra. Carmen Morales',
                        'customer_type' => 'retailer',
                        'credit_limit' => 7500.00,
                        'payment_terms' => '15 días',
                    ],
                ],
            ],

            // Customers for Grupo Industrial Salvadoreño (B2B manufacturing)
            [
                'company_slug' => 'grupo-industrial-salvadoreno',
                'customers' => [
                    [
                        'name' => 'Embotelladora La Cascada',
                        'legal_name' => 'Embotelladora La Cascada de Centroamérica, S.A. de C.V.',
                        'tax_id' => '0614-051199-207-6',
                        'email' => 'produccion@embotelladoralacascada.com',
                        'phone' => '+503-2012-3456',
                        'address' => 'Parque Industrial Merliot',
                        'city' => 'Santa Tecla',
                        'state' => 'La Libertad',
                        'country' => 'SV',
                        'postal_code' => '05101',
                        'contact_person' => 'Ing. Industrial Ana Pérez',
                        'customer_type' => 'manufacturer',
                        'credit_limit' => 25000.00,
                        'payment_terms' => '45 días',
                    ],
                    [
                        'name' => 'Fábrica de Alimentos Don Pedro',
                        'legal_name' => 'Industrias Alimenticias Don Pedro, S.A. de C.V.',
                        'tax_id' => '0614-271002-208-3',
                        'email' => 'operaciones@alimentosdonpedro.com.sv',
                        'phone' => '+503-2123-4567',
                        'address' => 'Zona Industrial de Apopa',
                        'city' => 'Apopa',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01115',
                        'contact_person' => 'Ing. Alimentos Pedro Ramírez',
                        'customer_type' => 'manufacturer',
                        'credit_limit' => 18000.00,
                        'payment_terms' => '30 días',
                    ],
                ],
            ],

            // Customers for Comercial Centroamericana (Export clients)
            [
                'company_slug' => 'comercial-centroamericana',
                'customers' => [
                    [
                        'name' => 'Guatemala Coffee Importers',
                        'legal_name' => 'Guatemala Coffee Importers, S.A.',
                        'tax_id' => 'GT-123456789',
                        'email' => 'purchases@guatemalacoffee.com.gt',
                        'phone' => '+502-2345-6789',
                        'address' => 'Zona 10, Guatemala City',
                        'city' => 'Guatemala City',
                        'state' => 'Guatemala',
                        'country' => 'GT',
                        'postal_code' => '01010',
                        'contact_person' => 'Sr. Carlos Mendoza',
                        'customer_type' => 'international',
                        'credit_limit' => 50000.00,
                        'payment_terms' => '60 días',
                    ],
                    [
                        'name' => 'US Specialty Foods Inc.',
                        'legal_name' => 'United States Specialty Foods Incorporated',
                        'tax_id' => 'US-987654321',
                        'email' => 'sourcing@usspecialtyfoods.com',
                        'phone' => '+1-305-234-5678',
                        'address' => '123 International Blvd, Miami',
                        'city' => 'Miami',
                        'state' => 'Florida',
                        'country' => 'US',
                        'postal_code' => '33101',
                        'contact_person' => 'Ms. Sarah Johnson',
                        'customer_type' => 'international',
                        'credit_limit' => 75000.00,
                        'payment_terms' => '90 días',
                    ],
                    [
                        'name' => 'Azucarera Hondureña',
                        'legal_name' => 'Compañía Azucarera de Honduras, S.A.',
                        'tax_id' => 'HN-456789123',
                        'email' => 'compras@azucareraparma.hn',
                        'phone' => '+504-2567-8901',
                        'address' => 'Boulevard Morazán, Tegucigalpa',
                        'city' => 'Tegucigalpa',
                        'state' => 'Francisco Morazán',
                        'country' => 'HN',
                        'postal_code' => '11101',
                        'contact_person' => 'Ing. Marco Paredes',
                        'customer_type' => 'international',
                        'credit_limit' => 40000.00,
                        'payment_terms' => '45 días',
                    ],
                ],
            ],

            // Customers for Agropecuaria San Miguel (Fresh produce buyers)
            [
                'company_slug' => 'agropecuaria-san-miguel',
                'customers' => [
                    [
                        'name' => 'Mercado Central de Mayoristas',
                        'legal_name' => 'Asociación de Mayoristas del Mercado Central',
                        'tax_id' => '0614-051085-209-0',
                        'email' => 'administracion@mercadocentral.com.sv',
                        'phone' => '+503-2234-5678',
                        'address' => 'Mercado Central, 1a Av. Norte',
                        'city' => 'San Salvador',
                        'state' => 'San Salvador',
                        'country' => 'SV',
                        'postal_code' => '01101',
                        'contact_person' => 'Sr. Manuel Rodríguez',
                        'customer_type' => 'wholesale_market',
                        'credit_limit' => 20000.00,
                        'payment_terms' => '7 días',
                    ],
                    [
                        'name' => 'Jugos Naturales El Trópico',
                        'legal_name' => 'Procesadora de Jugos El Trópico, S.A. de C.V.',
                        'tax_id' => '0614-190907-210-7',
                        'email' => 'compras@jugostrópico.com.sv',
                        'phone' => '+503-2345-6789',
                        'address' => 'Carretera a Comalapa Km. 25',
                        'city' => 'Olocuilta',
                        'state' => 'La Paz',
                        'country' => 'SV',
                        'postal_code' => '09101',
                        'contact_person' => 'Ing. Agr. Patricia Vásquez',
                        'customer_type' => 'processor',
                        'credit_limit' => 15000.00,
                        'payment_terms' => '21 días',
                    ],
                    [
                        'name' => 'Quesería Artesanal El Campo',
                        'legal_name' => 'Productos Lácteos Artesanales El Campo, S.A. de C.V.',
                        'tax_id' => '0614-080603-211-4',
                        'email' => 'propietario@queseriaelcampo.com',
                        'phone' => '+503-2456-7890',
                        'address' => 'Cantón El Rosario, Sesori',
                        'city' => 'Sesori',
                        'state' => 'San Miguel',
                        'country' => 'SV',
                        'postal_code' => '03201',
                        'contact_person' => 'Sr. Domingo Chávez',
                        'customer_type' => 'artisanal',
                        'credit_limit' => 8000.00,
                        'payment_terms' => '14 días',
                    ],
                ],
            ],
        ];

        $totalCustomers = 0;
        foreach ($customers as $customerGroup) {
            $company = $companies->get($customerGroup['company_slug']);
            if (! $company) {
                continue;
            }

            foreach ($customerGroup['customers'] as $customerData) {
                DB::table('customers')->insert([
                    'company_id' => $company->id,
                    'name' => $customerData['name'],
                    'slug' => Str::slug($customerData['name']),
                    'legal_name' => $customerData['legal_name'],
                    'tax_id' => $customerData['tax_id'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'],
                    'billing_address' => $customerData['address'],
                    'billing_city' => $customerData['city'],
                    'billing_state' => $customerData['state'],
                    'billing_country' => $customerData['country'],
                    'billing_postal_code' => $customerData['postal_code'],
                    'shipping_address' => $customerData['address'],
                    'shipping_city' => $customerData['city'],
                    'shipping_state' => $customerData['state'],
                    'shipping_country' => $customerData['country'],
                    'shipping_postal_code' => $customerData['postal_code'],
                    'contact_person' => $customerData['contact_person'],
                    'payment_terms' => $customerData['payment_terms'],
                    'credit_limit' => $customerData['credit_limit'],
                    'discount_percentage' => $customerData['customer_type'] === 'international' ? 5.0 : 0.0,
                    'notes' => 'Tipo: '.$customerData['customer_type'].'. '.
                              (in_array($customerData['customer_type'], ['international', 'institutional']) ? 'Cliente prioritario.' : '').
                              ($customerData['country'] !== 'SV' ? ' Cliente internacional.' : ''),
                    'is_active' => true,
                    'active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->line("✓ {$customerData['name']} - {$company->name}");
                $totalCustomers++;
            }
        }

        $this->command->info('✓ Clientes salvadoreños creados exitosamente');
        $this->command->line("  - {$totalCustomers} clientes en total");
        $this->command->line('  - Tipos: restaurantes, hoteles, instituciones, retailers, mayoristas, manufactureros, internacionales');
        $this->command->line('  - Países: El Salvador, Guatemala, Honduras, Estados Unidos');
        $this->command->line('  - Términos de pago de 7 a 90 días según tipo de cliente');
    }
}
