<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ENADonorsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🤝 Creando donantes para la ENA...');

        $company = DB::table('companies')->where('slug', 'escuela-nacional-agricultura')->first();

        if (! $company) {
            $this->command->error('❌ Error: Empresa ENA no encontrada');

            return;
        }

        $donors = [
            [
                'name' => 'FAO El Salvador',
                'legal_name' => 'Organización de las Naciones Unidas para la Alimentación y la Agricultura',
                'donor_type' => 'international',
                'tax_id' => null,
                'email' => 'fao-sv@fao.org',
                'phone' => '+503 2209-4100',
                'website' => 'https://www.fao.org/el-salvador',
                'address' => 'Edificio Naciones Unidas, Boulevard Orden de Malta Sur',
                'city' => 'Antiguo Cuscatlán',
                'state' => 'La Libertad',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_person' => 'Representante FAO El Salvador',
                'contact_phone' => '+503 2209-4100',
                'contact_email' => 'fao-sv@fao.org',
                'rating' => 5,
                'notes' => 'Organización de las Naciones Unidas especializada en alimentación y agricultura. Donante histórico de la ENA con múltiples proyectos de cooperación.',
            ],
            [
                'name' => 'USAID El Salvador',
                'legal_name' => 'Agencia de los Estados Unidos para el Desarrollo Internacional',
                'donor_type' => 'international',
                'tax_id' => null,
                'email' => 'infosv@usaid.gov',
                'phone' => '+503 2501-2999',
                'website' => 'https://www.usaid.gov/el-salvador',
                'address' => 'Embajada de Estados Unidos, Boulevard Santa Elena',
                'city' => 'Antiguo Cuscatlán',
                'state' => 'La Libertad',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_person' => 'Director USAID El Salvador',
                'contact_phone' => '+503 2501-2999',
                'contact_email' => 'infosv@usaid.gov',
                'rating' => 5,
                'notes' => 'Agencia del gobierno de Estados Unidos para desarrollo internacional. Programas de cooperación en educación agrícola y seguridad alimentaria.',
            ],
            [
                'name' => 'Cooperación Española - AECID',
                'legal_name' => 'Agencia Española de Cooperación Internacional para el Desarrollo',
                'donor_type' => 'international',
                'tax_id' => null,
                'email' => 'otc@aecid.sv',
                'phone' => '+503 2298-0600',
                'website' => 'https://www.aecid.es',
                'address' => 'Oficina Técnica de Cooperación, Colonia San Benito',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_person' => 'Coordinador AECID El Salvador',
                'contact_phone' => '+503 2298-0600',
                'contact_email' => 'otc@aecid.sv',
                'rating' => 5,
                'notes' => 'Agencia de cooperación del gobierno de España. Apoyo a instituciones educativas en El Salvador.',
            ],
            [
                'name' => 'Banco Interamericano de Desarrollo',
                'legal_name' => 'BID - Banco Interamericano de Desarrollo',
                'donor_type' => 'international',
                'tax_id' => null,
                'email' => 'countries@iadb.org',
                'phone' => '+503 2233-8900',
                'website' => 'https://www.iadb.org',
                'address' => 'Edificio BID, 89 Avenida Norte',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_person' => 'Representante BID El Salvador',
                'contact_phone' => '+503 2233-8900',
                'contact_email' => 'countries@iadb.org',
                'rating' => 5,
                'notes' => 'Banco de desarrollo multilateral. Financiamiento para proyectos de educación técnica y agrícola.',
            ],
            [
                'name' => 'Fundación Salvadoreña para el Desarrollo Económico y Social',
                'legal_name' => 'FUSADES - Fundación Salvadoreña para el Desarrollo Económico y Social',
                'donor_type' => 'organization',
                'tax_id' => '0614-140677-001-9',
                'email' => 'info@fusades.org',
                'phone' => '+503 2248-5600',
                'website' => 'https://www.fusades.org',
                'address' => 'Boulevard y Urbanización Santa Elena',
                'city' => 'Antiguo Cuscatlán',
                'state' => 'La Libertad',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_person' => 'Director Ejecutivo FUSADES',
                'contact_phone' => '+503 2248-5600',
                'contact_email' => 'info@fusades.org',
                'rating' => 5,
                'notes' => 'Institución privada de desarrollo. Programas de formación técnica y empresarial para el sector agrícola.',
            ],
            [
                'name' => 'Ministerio de Agricultura y Ganadería',
                'legal_name' => 'MAG - Ministerio de Agricultura y Ganadería de El Salvador',
                'donor_type' => 'government',
                'tax_id' => null,
                'email' => 'comunicaciones@mag.gob.sv',
                'phone' => '+503 2210-1700',
                'website' => 'https://www.mag.gob.sv',
                'address' => 'Final 1a. Avenida Norte y Av. Manuel Gallardo',
                'city' => 'San Salvador',
                'state' => 'San Salvador',
                'country' => 'SV',
                'postal_code' => '01101',
                'contact_person' => 'Ministro de Agricultura y Ganadería',
                'contact_phone' => '+503 2210-1700',
                'contact_email' => 'comunicaciones@mag.gob.sv',
                'rating' => 5,
                'notes' => 'Ministerio rector del sector agropecuario en El Salvador. Donaciones de insumos y equipamiento para fines educativos.',
            ],
        ];

        foreach ($donors as $donor) {
            DB::table('donors')->insert([
                'company_id' => $company->id,
                'name' => $donor['name'],
                'slug' => \Illuminate\Support\Str::slug($donor['name']),
                'legal_name' => $donor['legal_name'],
                'donor_type' => $donor['donor_type'],
                'tax_id' => $donor['tax_id'],
                'email' => $donor['email'],
                'phone' => $donor['phone'],
                'website' => $donor['website'],
                'address' => $donor['address'],
                'city' => $donor['city'],
                'state' => $donor['state'],
                'country' => $donor['country'],
                'postal_code' => $donor['postal_code'],
                'contact_person' => $donor['contact_person'],
                'contact_phone' => $donor['contact_phone'],
                'contact_email' => $donor['contact_email'],
                'rating' => $donor['rating'],
                'notes' => $donor['notes'],
                'is_active' => true,
                'active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->line("✓ Donante creado: {$donor['name']}");
        }

        $this->command->info('✅ 6 donantes creados exitosamente');
    }
}
