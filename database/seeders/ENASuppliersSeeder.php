<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ENASuppliersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏪 Creando proveedores para la ENA...');

        $company = DB::table('companies')->where('slug', 'escuela-nacional-agricultura')->first();

        if (! $company) {
            $this->command->error('❌ Error: Empresa ENA no encontrada');

            return;
        }

        $suppliers = [
            [
                'code' => 'PROV-001',
                'name' => 'DISAGRO S.A. de C.V.',
                'legal_name' => 'Distribuidora Agropecuaria de El Salvador S.A. de C.V.',
                'tax_id' => '0614-250589-101-5',
                'email' => 'ventas@disagro.com.sv',
                'phone' => '+503 2250-8800',
                'address' => 'Boulevard del Ejército Nacional, San Salvador',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_name' => 'Roberto Martínez',
                'contact_phone' => '+503 7890-1234',
                'contact_email' => 'rmartinez@disagro.com.sv',
                'supplier_type' => 'Insumos Agrícolas',
                'payment_terms' => '30 días',
                'credit_limit' => 10000.00,
            ],
            [
                'code' => 'PROV-002',
                'name' => 'Concentrados La Pradera',
                'legal_name' => 'Alimentos Balanceados La Pradera S.A.',
                'tax_id' => '0614-180678-102-3',
                'email' => 'pedidos@lapradera.com.sv',
                'phone' => '+503 2245-7700',
                'address' => 'Carretera Panamericana Km 12.5, Soyapango',
                'city' => 'Soyapango',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01120',
                'contact_name' => 'María Fernández',
                'contact_phone' => '+503 7123-4567',
                'contact_email' => 'mfernandez@lapradera.com.sv',
                'supplier_type' => 'Alimentos para Ganado',
                'payment_terms' => '15 días',
                'credit_limit' => 8000.00,
            ],
            [
                'code' => 'PROV-003',
                'name' => 'Ferretería El Constructor',
                'legal_name' => 'Ferretería y Materiales El Constructor S.A. de C.V.',
                'tax_id' => '0614-301289-103-1',
                'email' => 'ventas@elconstructor.com.sv',
                'phone' => '+503 2222-5500',
                'address' => 'Avenida España, San Salvador',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_name' => 'Carlos López',
                'contact_phone' => '+503 7234-5678',
                'contact_email' => 'clopez@elconstructor.com.sv',
                'supplier_type' => 'Ferretería y Herramientas',
                'payment_terms' => '45 días',
                'credit_limit' => 5000.00,
            ],
            [
                'code' => 'PROV-004',
                'name' => 'Azúcar y Derivados S.A.',
                'legal_name' => 'Industria Azucarera y Derivados S.A. de C.V.',
                'tax_id' => '0614-220490-104-8',
                'email' => 'compras@azucarderivados.com.sv',
                'phone' => '+503 2260-3300',
                'address' => 'Zona Industrial La Libertad',
                'city' => 'La Libertad',
                'state' => 'La Libertad',
                'country' => 'SV',
                'postal_code' => '02101',
                'contact_name' => 'Ana Rodríguez',
                'contact_phone' => '+503 7345-6789',
                'contact_email' => 'arodriguez@azucarderivados.com.sv',
                'supplier_type' => 'Insumos Agroindustriales',
                'payment_terms' => '30 días',
                'credit_limit' => 6000.00,
            ],
            [
                'code' => 'PROV-005',
                'name' => 'Veterinaria San Francisco',
                'legal_name' => 'Productos Veterinarios San Francisco S.A. de C.V.',
                'tax_id' => '0614-150378-105-6',
                'email' => 'info@vetsanfrancisco.com.sv',
                'phone' => '+503 2235-4400',
                'address' => 'Boulevard Los Héroes, San Salvador',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_name' => 'Dr. José Hernández',
                'contact_phone' => '+503 7456-7890',
                'contact_email' => 'jhernandez@vetsanfrancisco.com.sv',
                'supplier_type' => 'Medicamentos Veterinarios',
                'payment_terms' => '20 días',
                'credit_limit' => 4000.00,
            ],
        ];

        foreach ($suppliers as $supplier) {
            DB::table('suppliers')->insert([
                'company_id' => $company->id,
                'name' => $supplier['name'],
                'slug' => \Illuminate\Support\Str::slug($supplier['name']),
                'legal_name' => $supplier['legal_name'],
                'tax_id' => $supplier['tax_id'],
                'email' => $supplier['email'],
                'phone' => $supplier['phone'],
                'address' => $supplier['address'],
                'city' => $supplier['city'],
                'state' => $supplier['state'],
                'country' => $supplier['country'],
                'postal_code' => $supplier['postal_code'],
                'contact_person' => $supplier['contact_name'],
                'contact_phone' => $supplier['contact_phone'],
                'contact_email' => $supplier['contact_email'],
                'payment_terms' => $supplier['payment_terms'],
                'credit_limit' => $supplier['credit_limit'],
                'notes' => 'Tipo: '.$supplier['supplier_type'],
                'is_active' => true,
                'active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->line("✓ Proveedor creado: {$supplier['name']}");
        }

        $this->command->info('✅ 5 proveedores creados exitosamente');
    }
}
