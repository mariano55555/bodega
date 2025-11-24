<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ElSalvadorGeographySeeder extends Seeder
{
    /**
     * Seed the 14 departments and their municipalities (cities) of El Salvador.
     */
    public function run(): void
    {
        $this->command->info('🗺️  Creando departamentos y ciudades de El Salvador...');

        $geographyData = [
            // 1. AHUACHAPÁN
            [
                'departamento' => ['code' => 'AH', 'name' => 'Ahuachapán'],
                'ciudades' => [
                    'Ahuachapán', 'Apaneca', 'Atiquizaya', 'Concepción de Ataco', 'El Refugio',
                    'Guaymango', 'Jujutla', 'San Francisco Menéndez', 'San Lorenzo', 'San Pedro Puxtla',
                    'Tacuba', 'Turín',
                ],
            ],
            // 2. CABAÑAS
            [
                'departamento' => ['code' => 'CA', 'name' => 'Cabañas'],
                'ciudades' => [
                    'Sensuntepeque', 'Cinquera', 'Dolores', 'Guacotecti', 'Ilobasco',
                    'Jutiapa', 'San Isidro', 'Tejutepeque', 'Victoria',
                ],
            ],
            // 3. CHALATENANGO
            [
                'departamento' => ['code' => 'CH', 'name' => 'Chalatenango'],
                'ciudades' => [
                    'Chalatenango', 'Agua Caliente', 'Arcatao', 'Azacualpa', 'Cancasque',
                    'Citalá', 'Comalapa', 'Concepción Quezaltepeque', 'Dulce Nombre de María', 'El Carrizal',
                    'El Paraíso', 'La Laguna', 'La Palma', 'La Reina', 'Las Vueltas',
                    'Nombre de Jesús', 'Nueva Concepción', 'Nueva Trinidad', 'Ojos de Agua', 'Potonico',
                    'San Antonio de la Cruz', 'San Antonio Los Ranchos', 'San Fernando', 'San Francisco Lempa', 'San Francisco Morazán',
                    'San Ignacio', 'San Isidro Labrador', 'San José Cancasque', 'San José Las Flores', 'San Luis del Carmen',
                    'San Miguel de Mercedes', 'San Rafael', 'Santa Rita', 'Tejutla',
                ],
            ],
            // 4. CUSCATLÁN
            [
                'departamento' => ['code' => 'CU', 'name' => 'Cuscatlán'],
                'ciudades' => [
                    'Cojutepeque', 'Candelaria', 'El Carmen', 'El Rosario', 'Monte San Juan',
                    'Oratorio de Concepción', 'San Bartolomé Perulapía', 'San Cristóbal', 'San José Guayabal', 'San Pedro Perulapán',
                    'San Rafael Cedros', 'San Ramón', 'Santa Cruz Analquito', 'Santa Cruz Michapa', 'Suchitoto',
                    'Tenancingo',
                ],
            ],
            // 5. LA LIBERTAD
            [
                'departamento' => ['code' => 'LI', 'name' => 'La Libertad'],
                'ciudades' => [
                    'Santa Tecla', 'Antiguo Cuscatlán', 'Chiltiupán', 'Ciudad Arce', 'Colón',
                    'Comasagua', 'Huizúcar', 'Jayaque', 'Jicalapa', 'La Libertad',
                    'Nuevo Cuscatlán', 'Quezaltepeque', 'Sacacoyo', 'San José Villanueva', 'San Juan Opico',
                    'San Matías', 'San Pablo Tacachico', 'Talnique', 'Tamanique', 'Teotepeque',
                    'Tepecoyo', 'Zaragoza',
                ],
            ],
            // 6. LA PAZ
            [
                'departamento' => ['code' => 'PA', 'name' => 'La Paz'],
                'ciudades' => [
                    'Zacatecoluca', 'Cuyultitán', 'El Rosario', 'Jerusalén', 'Mercedes La Ceiba',
                    'Olocuilta', 'Paraíso de Osorio', 'San Antonio Masahuat', 'San Emigdio', 'San Francisco Chinameca',
                    'San Juan Nonualco', 'San Juan Talpa', 'San Juan Tepezontes', 'San Luis La Herradura', 'San Luis Talpa',
                    'San Miguel Tepezontes', 'San Pedro Masahuat', 'San Pedro Nonualco', 'San Rafael Obrajuelo', 'Santa María Ostuma',
                    'Santiago Nonualco', 'Tapalhuaca',
                ],
            ],
            // 7. LA UNIÓN
            [
                'departamento' => ['code' => 'UN', 'name' => 'La Unión'],
                'ciudades' => [
                    'La Unión', 'Anamorós', 'Bolívar', 'Concepción de Oriente', 'Conchagua',
                    'El Carmen', 'El Sauce', 'Intipucá', 'Lislique', 'Meanguera del Golfo',
                    'Nueva Esparta', 'Pasaquina', 'Polorós', 'San Alejo', 'San José',
                    'Santa Rosa de Lima', 'Yayantique', 'Yucuaiquín',
                ],
            ],
            // 8. MORAZÁN
            [
                'departamento' => ['code' => 'MO', 'name' => 'Morazán'],
                'ciudades' => [
                    'San Francisco Gotera', 'Arambala', 'Cacaopera', 'Chilanga', 'Corinto',
                    'Delicias de Concepción', 'El Divisadero', 'El Rosario', 'Gualococti', 'Guatajiagua',
                    'Joateca', 'Jocoaitique', 'Jocoro', 'Lolotiquillo', 'Meanguera',
                    'Osicala', 'Perquín', 'San Carlos', 'San Fernando', 'San Isidro',
                    'San Simón', 'Sensembra', 'Sociedad', 'Torola', 'Yamabal',
                    'Yoloaiquín',
                ],
            ],
            // 9. SAN MIGUEL
            [
                'departamento' => ['code' => 'SM', 'name' => 'San Miguel'],
                'ciudades' => [
                    'San Miguel', 'Carolina', 'Chapeltique', 'Chinameca', 'Chirilagua',
                    'Ciudad Barrios', 'Comacarán', 'El Tránsito', 'Lolotique', 'Moncagua',
                    'Nueva Guadalupe', 'Nuevo Edén de San Juan', 'Quelepa', 'San Antonio del Mosco', 'San Gerardo',
                    'San Jorge', 'San Luis de la Reina', 'San Rafael Oriente', 'Sesori', 'Uluazapa',
                ],
            ],
            // 10. SAN SALVADOR
            [
                'departamento' => ['code' => 'SS', 'name' => 'San Salvador'],
                'ciudades' => [
                    'San Salvador', 'Aguilares', 'Apopa', 'Ayutuxtepeque', 'Cuscatancingo',
                    'Delgado', 'El Paisnal', 'Guazapa', 'Ilopango', 'Mejicanos',
                    'Nejapa', 'Panchimalco', 'Rosario de Mora', 'San Marcos', 'San Martín',
                    'Santiago Texacuangos', 'Santo Tomás', 'Soyapango', 'Tonacatepeque',
                ],
            ],
            // 11. SAN VICENTE
            [
                'departamento' => ['code' => 'SV', 'name' => 'San Vicente'],
                'ciudades' => [
                    'San Vicente', 'Apastepeque', 'Guadalupe', 'San Cayetano Istepeque', 'San Esteban Catarina',
                    'San Ildefonso', 'San Lorenzo', 'San Sebastián', 'Santa Clara', 'Santo Domingo',
                    'Tecoluca', 'Tepetitán', 'Verapaz',
                ],
            ],
            // 12. SANTA ANA
            [
                'departamento' => ['code' => 'SA', 'name' => 'Santa Ana'],
                'ciudades' => [
                    'Santa Ana', 'Candelaria de la Frontera', 'Chalchuapa', 'Coatepeque', 'El Congo',
                    'El Porvenir', 'Masahuat', 'Metapán', 'San Antonio Pajonal', 'San Sebastián Salitrillo',
                    'Santa Rosa Guachipilín', 'Santiago de la Frontera', 'Texistepeque',
                ],
            ],
            // 13. SONSONATE
            [
                'departamento' => ['code' => 'SO', 'name' => 'Sonsonate'],
                'ciudades' => [
                    'Sonsonate', 'Acajutla', 'Armenia', 'Caluco', 'Cuisnahuat',
                    'Izalco', 'Juayúa', 'Nahuizalco', 'Nahulingo', 'Salcoatitán',
                    'San Antonio del Monte', 'San Julián', 'Santa Catarina Masahuat', 'Santa Isabel Ishuatán', 'Santo Domingo de Guzmán',
                    'Sonzacate',
                ],
            ],
            // 14. USULUTÁN
            [
                'departamento' => ['code' => 'US', 'name' => 'Usulután'],
                'ciudades' => [
                    'Usulután', 'Alegría', 'Berlín', 'California', 'Concepción Batres',
                    'El Triunfo', 'Ereguayquín', 'Estanzuelas', 'Jiquilisco', 'Jucuapa',
                    'Jucuarán', 'Mercedes Umaña', 'Nueva Granada', 'Ozatlán', 'Puerto El Triunfo',
                    'San Agustín', 'San Buenaventura', 'San Dionisio', 'San Francisco Javier', 'Santa Elena',
                    'Santa María', 'Santiago de María', 'Tecapán',
                ],
            ],
        ];

        $totalDepartamentos = 0;
        $totalCiudades = 0;

        foreach ($geographyData as $data) {
            // Insert departamento
            $departamentoId = DB::table('departamentos')->insertGetId([
                'code' => $data['departamento']['code'],
                'name' => $data['departamento']['name'],
                'slug' => Str::slug($data['departamento']['name']),
                'is_active' => true,
                'active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalDepartamentos++;
            $this->command->line("✓ Departamento: {$data['departamento']['name']}");

            // Insert ciudades for this departamento
            foreach ($data['ciudades'] as $index => $ciudadName) {
                DB::table('ciudades')->insert([
                    'departamento_id' => $departamentoId,
                    'code' => str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'name' => $ciudadName,
                    'slug' => Str::slug($ciudadName),
                    'is_active' => true,
                    'active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $totalCiudades++;
            }

            $this->command->line("  → {$totalCiudades} ciudades");
        }

        $this->command->info("✅ {$totalDepartamentos} departamentos y {$totalCiudades} ciudades creados exitosamente");
    }
}
