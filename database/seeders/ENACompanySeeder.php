<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ENACompanySeeder extends Seeder
{
    /**
     * Seed the ENA (Escuela Nacional de Agricultura) company and branch.
     */
    public function run(): void
    {
        $this->command->info('🎓 Creando empresa: Escuela Nacional de Agricultura...');

        // Create ENA Company
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Escuela Nacional de Agricultura',
            'slug' => 'escuela-nacional-agricultura',
            'legal_name' => 'Escuela Nacional de Agricultura "Roberto Quiñónez"',
            'tax_id' => '0614-310579-101-8', // NIT El Salvador format
            'email' => 'info@ena.gob.sv',
            'phone' => '+503 2228-1122',
            'website' => 'https://www.mag.gob.sv/ena',
            'address' => 'Kilómetro 33.5 Carretera a Santa Tecla',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'default_currency' => 'USD',
            'timezone' => 'America/El_Salvador',
            'settings' => json_encode([
                'institution_type' => 'educational',
                'government_entity' => true,
                'ministry' => 'MAG',
                'fiscal_year_start' => '01-01',
                'requires_approval_for_purchases' => true,
                'requires_approval_for_transfers' => true,
                'requires_approval_for_adjustments' => true,
                'tracks_projects' => true,
                'tracks_cost_centers' => true,
                'latitude' => 13.6773,
                'longitude' => -89.2797,
            ]),
            'is_active' => true,
            'active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Empresa creada: Escuela Nacional de Agricultura (ID: {$companyId})");

        // Create Campus Central Branch
        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Campus Central Santa Tecla',
            'slug' => 'campus-central-santa-tecla',
            'code' => 'ENA-CC-001',
            'description' => 'Campus principal de la Escuela Nacional de Agricultura ubicado en Santa Tecla, La Libertad. Incluye áreas de cultivos, ganadería, procesamiento agroindustrial y mantenimiento.',
            'address' => 'Kilómetro 33.5 Carretera a Santa Tecla',
            'city' => 'Santa Tecla',
            'state' => 'La Libertad',
            'country' => 'SV',
            'postal_code' => '01101',
            'manager_id' => null, // Will be set after creating users
            'is_active' => true,
            'active_at' => now(),
            'settings' => json_encode([
                'campus_type' => 'main',
                'is_headquarters' => true,
                'has_student_housing' => true,
                'has_cafeteria' => true,
                'total_hectares' => 120,
                'student_capacity' => 500,
                'email' => 'campus@ena.gob.sv',
                'phone' => '+503 2228-1122',
                'latitude' => 13.6773,
                'longitude' => -89.2797,
                'operating_days' => [
                    'monday' => true,
                    'tuesday' => true,
                    'wednesday' => true,
                    'thursday' => true,
                    'friday' => true,
                    'saturday' => true,
                    'sunday' => false,
                ],
                'operating_hours' => [
                    'monday' => '07:00-17:00',
                    'tuesday' => '07:00-17:00',
                    'wednesday' => '07:00-17:00',
                    'thursday' => '07:00-17:00',
                    'friday' => '07:00-17:00',
                    'saturday' => '07:00-12:00',
                    'sunday' => 'closed',
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("✓ Sucursal creada: Campus Central Santa Tecla (ID: {$branchId})");

        $this->command->info('✅ Empresa ENA y Campus Central creados exitosamente');
    }
}
