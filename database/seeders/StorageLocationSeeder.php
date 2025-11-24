<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorageLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic storage locations for El Salvador warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando ubicaciones de almacenamiento...');

        // Get warehouses
        $warehouses = DB::table('warehouses')->get();

        $totalLocations = 0;

        foreach ($warehouses as $warehouse) {
            $locations = $this->getLocationsForWarehouse($warehouse);

            foreach ($locations as $locationData) {
                // Map location_type to the appropriate type enum value
                $typeMapping = [
                    'receiving' => 'dock',
                    'shipping' => 'dock',
                    'shelf' => 'shelf',
                    'bulk' => 'zone',
                    'cold_storage' => 'zone',
                    'freezer' => 'zone',
                    'transition' => 'zone',
                    'packing' => 'zone',
                    'hazmat' => 'zone',
                    'heavy_duty' => 'zone',
                    'quality_control' => 'zone',
                    'finished_goods' => 'shelf',
                    'shipping_prep' => 'staging',
                    'dock' => 'dock',
                    'transit' => 'zone',
                    'inspection' => 'zone',
                    'consolidation' => 'zone',
                    'picking' => 'zone',
                    'temporary' => 'staging',
                ];

                DB::table('storage_locations')->insert([
                    'warehouse_id' => $warehouse->id,
                    'company_id' => $warehouse->company_id,
                    'name' => $locationData['name'],
                    'slug' => Str::slug($locationData['name']),
                    'code' => $locationData['code'],
                    'type' => $typeMapping[$locationData['location_type']] ?? 'zone',
                    'description' => $locationData['description'],
                    'section' => $locationData['rack'] ?? null, // Map rack to section
                    'aisle' => $locationData['aisle'] ?? null,
                    'shelf' => $locationData['shelf'] ?? null,
                    'bin' => $locationData['bin'] ?? null,
                    'capacity' => $locationData['capacity_volume'] ?? null, // Use volume as main capacity
                    'weight_limit' => $locationData['capacity_weight'] ?? null,
                    'is_active' => true,
                    'active_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->line("  ✓ {$locationData['name']} - {$warehouse->name}");
                $totalLocations++;
            }
        }

        $this->command->info('✓ Ubicaciones de almacenamiento creadas exitosamente');
        $this->command->line("  - {$totalLocations} ubicaciones en total");
        $this->command->line('  - Tipos: estanterías, pasillos, zonas especializadas, áreas de recepción/despacho');
        $this->command->line('  - Organización sistemática por tipo de almacén salvadoreño');
    }

    private function getLocationsForWarehouse($warehouse): array
    {
        $warehouseName = $warehouse->name;

        if (str_contains($warehouseName, 'Distribución') || str_contains($warehouseName, 'Central')) {
            return $this->getDistributionWarehouseLocations();
        } elseif (str_contains($warehouseName, 'Frigorífico') || str_contains($warehouseName, 'Refrigerado')) {
            return $this->getColdStorageLocations();
        } elseif (str_contains($warehouseName, 'Materias Primas')) {
            return $this->getRawMaterialsLocations();
        } elseif (str_contains($warehouseName, 'Productos Terminados')) {
            return $this->getFinishedGoodsLocations();
        } elseif (str_contains($warehouseName, 'Portuario')) {
            return $this->getPortWarehouseLocations();
        } else {
            return $this->getGeneralWarehouseLocations();
        }
    }

    private function getDistributionWarehouseLocations(): array
    {
        return [
            // Área de recepción
            [
                'name' => 'Área de Recepción Principal',
                'code' => 'REC-01',
                'description' => 'Zona principal para recepción de mercancías y productos',
                'location_type' => 'receiving',
                'aisle' => 'R',
                'capacity_volume' => 200.0,
                'capacity_weight' => 5000.0,
                'settings' => ['prioridad' => 'alta', 'requiere_inspeccion' => true],
            ],
            [
                'name' => 'Área de Recepción Secundaria',
                'code' => 'REC-02',
                'description' => 'Zona secundaria para exceso de recepción de mercancías',
                'location_type' => 'receiving',
                'aisle' => 'R',
                'capacity_volume' => 150.0,
                'capacity_weight' => 3000.0,
                'settings' => ['prioridad' => 'media', 'requiere_inspeccion' => true],
            ],

            // Pasillos principales de almacenamiento
            [
                'name' => 'Pasillo A - Alimentos Secos',
                'code' => 'A-01',
                'description' => 'Estanterías para productos alimenticios secos y enlatados',
                'location_type' => 'shelf',
                'aisle' => 'A',
                'rack' => '01',
                'shelf' => '01',
                'capacity_volume' => 500.0,
                'capacity_weight' => 8000.0,
                'settings' => ['tipo_producto' => 'alimentos_secos', 'control_temperatura' => false],
            ],
            [
                'name' => 'Pasillo A - Bebidas y Refrescos',
                'code' => 'A-02',
                'description' => 'Estanterías para bebidas, refrescos y jugos envasados',
                'location_type' => 'shelf',
                'aisle' => 'A',
                'rack' => '02',
                'shelf' => '01',
                'capacity_volume' => 600.0,
                'capacity_weight' => 12000.0,
                'settings' => ['tipo_producto' => 'bebidas', 'fragil' => true],
            ],
            [
                'name' => 'Pasillo B - Productos de Limpieza',
                'code' => 'B-01',
                'description' => 'Área para productos de limpieza, detergentes e higiene personal',
                'location_type' => 'shelf',
                'aisle' => 'B',
                'rack' => '01',
                'shelf' => '01',
                'capacity_volume' => 400.0,
                'capacity_weight' => 6000.0,
                'settings' => ['tipo_producto' => 'limpieza', 'materiales_peligrosos' => false],
            ],
            [
                'name' => 'Pasillo C - Granos y Cereales',
                'code' => 'C-01',
                'description' => 'Almacenamiento de granos básicos, cereales y productos a granel',
                'location_type' => 'bulk',
                'aisle' => 'C',
                'capacity_volume' => 800.0,
                'capacity_weight' => 15000.0,
                'settings' => ['tipo_producto' => 'granos_granel', 'control_plagas' => true],
            ],

            // Área de despacho
            [
                'name' => 'Área de Despacho Norte',
                'code' => 'DES-N1',
                'description' => 'Zona de despacho para rutas hacia el norte del país',
                'location_type' => 'shipping',
                'aisle' => 'D',
                'capacity_volume' => 300.0,
                'capacity_weight' => 7000.0,
                'settings' => ['zona_ruta' => 'norte', 'muelle_carga' => 'N1,N2'],
            ],
            [
                'name' => 'Área de Despacho Sur',
                'code' => 'DES-S1',
                'description' => 'Zona de despacho para rutas hacia el sur del país',
                'location_type' => 'shipping',
                'aisle' => 'D',
                'capacity_volume' => 300.0,
                'capacity_weight' => 7000.0,
                'settings' => ['zona_ruta' => 'sur', 'muelle_carga' => 'S1,S2'],
            ],
        ];
    }

    private function getColdStorageLocations(): array
    {
        return [
            [
                'name' => 'Cámara Frigorífica 1 (0-4°C)',
                'code' => 'FRIO-01',
                'description' => 'Refrigeración para lácteos, quesos y productos frescos salvadoreños',
                'location_type' => 'cold_storage',
                'aisle' => 'F1',
                'capacity_volume' => 400.0,
                'capacity_weight' => 8000.0,
                'settings' => ['temperatura_min' => 0, 'temperatura_max' => 4, 'tipo_producto' => 'lacteos_frescos'],
            ],
            [
                'name' => 'Cámara de Congelación (-18°C)',
                'code' => 'CONG-01',
                'description' => 'Congelación para productos congelados y helados',
                'location_type' => 'freezer',
                'aisle' => 'F2',
                'capacity_volume' => 300.0,
                'capacity_weight' => 6000.0,
                'settings' => ['temperatura_min' => -20, 'temperatura_max' => -15, 'tipo_producto' => 'congelados'],
            ],
            [
                'name' => 'Antecámara de Transición',
                'code' => 'ANTE-01',
                'description' => 'Zona de transición entre temperatura ambiente y refrigeración',
                'location_type' => 'transition',
                'aisle' => 'T',
                'capacity_volume' => 100.0,
                'capacity_weight' => 2000.0,
                'settings' => ['temperatura_min' => 10, 'temperatura_max' => 15, 'almacenamiento_temporal' => true],
            ],
            [
                'name' => 'Área de Empaque Refrigerado',
                'code' => 'EMP-FRIO',
                'description' => 'Zona de empaque y preparación en ambiente refrigerado controlado',
                'location_type' => 'packing',
                'aisle' => 'E',
                'capacity_volume' => 150.0,
                'capacity_weight' => 3000.0,
                'settings' => ['temperatura_min' => 8, 'temperatura_max' => 12, 'estacion_empaque' => true],
            ],
        ];
    }

    private function getRawMaterialsLocations(): array
    {
        return [
            [
                'name' => 'Zona de Químicos Industriales',
                'code' => 'QUIM-01',
                'description' => 'Almacenamiento seguro de productos químicos y materiales industriales',
                'location_type' => 'hazmat',
                'aisle' => 'Q',
                'capacity_volume' => 200.0,
                'capacity_weight' => 4000.0,
                'settings' => ['materiales_peligrosos' => true, 'ventilacion_requerida' => true, 'supresion_incendios' => 'especial'],
            ],
            [
                'name' => 'Almacén de Materiales Plásticos',
                'code' => 'PLAS-01',
                'description' => 'Materias primas plásticas, polímeros y resinas para manufactura',
                'location_type' => 'bulk',
                'aisle' => 'P',
                'capacity_volume' => 600.0,
                'capacity_weight' => 8000.0,
                'settings' => ['tipo_producto' => 'plasticos', 'control_estatico' => true],
            ],
            [
                'name' => 'Depósito de Metales y Aleaciones',
                'code' => 'MET-01',
                'description' => 'Almacenamiento de metales, aleaciones y materiales pesados',
                'location_type' => 'heavy_duty',
                'aisle' => 'M',
                'capacity_volume' => 300.0,
                'capacity_weight' => 15000.0,
                'settings' => ['tipo_producto' => 'metales', 'carga_pesada' => true, 'acceso_grua' => true],
            ],
            [
                'name' => 'Laboratorio de Control de Calidad',
                'code' => 'QC-01',
                'description' => 'Laboratorio de análisis y área de almacenamiento de muestras',
                'location_type' => 'quality_control',
                'aisle' => 'Q',
                'capacity_volume' => 50.0,
                'capacity_weight' => 500.0,
                'settings' => ['area_pruebas' => true, 'almacenamiento_muestras' => true, 'acceso_restringido' => true],
            ],
        ];
    }

    private function getFinishedGoodsLocations(): array
    {
        return [
            [
                'name' => 'Zona Productos Plásticos Terminados',
                'code' => 'PROD-A1',
                'description' => 'Envases, contenedores y productos plásticos terminados listos para distribución',
                'location_type' => 'finished_goods',
                'aisle' => 'A',
                'rack' => '01',
                'capacity_volume' => 800.0,
                'capacity_weight' => 5000.0,
                'settings' => ['categoria_producto' => 'envases', 'apilable' => true],
            ],
            [
                'name' => 'Zona Productos Metálicos Terminados',
                'code' => 'PROD-B1',
                'description' => 'Herramientas, piezas metálicas y productos manufacturados',
                'location_type' => 'finished_goods',
                'aisle' => 'B',
                'rack' => '01',
                'capacity_volume' => 400.0,
                'capacity_weight' => 8000.0,
                'settings' => ['categoria_producto' => 'herramientas', 'trabajo_pesado' => true],
            ],
            [
                'name' => 'Área de Expedición y Embarque',
                'code' => 'EXP-01',
                'description' => 'Preparación y consolidación para envío de productos terminados',
                'location_type' => 'shipping_prep',
                'aisle' => 'E',
                'capacity_volume' => 200.0,
                'capacity_weight' => 3000.0,
                'settings' => ['despacho' => true, 'estacion_empaque' => true],
            ],
        ];
    }

    private function getPortWarehouseLocations(): array
    {
        return [
            [
                'name' => 'Muelle de Carga Principal',
                'code' => 'MUEL-A',
                'description' => 'Zona de carga y descarga de contenedores marítimos y terrestres',
                'location_type' => 'dock',
                'aisle' => 'M',
                'capacity_volume' => 1000.0,
                'capacity_weight' => 25000.0,
                'settings' => ['acceso_contenedores' => true, 'operacion_grua' => true, 'zona_aduanera' => true],
            ],
            [
                'name' => 'Almacén de Tránsito Internacional',
                'code' => 'TRANS-01',
                'description' => 'Mercancías en tránsito internacional con destino a Guatemala y Honduras',
                'location_type' => 'transit',
                'aisle' => 'T',
                'capacity_volume' => 800.0,
                'capacity_weight' => 20000.0,
                'settings' => ['deposito_aduanero' => true, 'almacenamiento_temporal' => true, 'nivel_seguridad' => 'alto'],
            ],
            [
                'name' => 'Zona de Inspección DGA',
                'code' => 'ADUAN-01',
                'description' => 'Área para inspección de mercancías por la Dirección General de Aduanas',
                'location_type' => 'inspection',
                'aisle' => 'I',
                'capacity_volume' => 200.0,
                'capacity_weight' => 5000.0,
                'settings' => ['inspeccion_aduanera' => true, 'acceso_restringido' => true],
            ],
            [
                'name' => 'Área de Consolidación de Exportación',
                'code' => 'EXP-CONT',
                'description' => 'Área de consolidación de mercancías para exportación hacia Centroamérica',
                'location_type' => 'consolidation',
                'aisle' => 'C',
                'capacity_volume' => 600.0,
                'capacity_weight' => 15000.0,
                'settings' => ['zona_exportacion' => true, 'consolidacion' => true],
            ],
        ];
    }

    private function getGeneralWarehouseLocations(): array
    {
        return [
            [
                'name' => 'Estantería Principal Nivel 1',
                'code' => 'EST-A1',
                'description' => 'Estantería de uso general de fácil acceso - primer nivel',
                'location_type' => 'shelf',
                'aisle' => 'A',
                'rack' => '01',
                'shelf' => '01',
                'capacity_volume' => 100.0,
                'capacity_weight' => 2000.0,
                'settings' => ['uso_general' => true, 'accesibilidad' => 'montacargas'],
            ],
            [
                'name' => 'Estantería Principal Nivel 2',
                'code' => 'EST-A2',
                'description' => 'Estantería de uso general - segundo nivel con acceso manual',
                'location_type' => 'shelf',
                'aisle' => 'A',
                'rack' => '01',
                'shelf' => '02',
                'capacity_volume' => 100.0,
                'capacity_weight' => 1500.0,
                'settings' => ['uso_general' => true, 'accesibilidad' => 'manual'],
            ],
            [
                'name' => 'Área de Preparación de Pedidos',
                'code' => 'PICK-01',
                'description' => 'Zona especializada para preparación y selección de pedidos',
                'location_type' => 'picking',
                'aisle' => 'P',
                'capacity_volume' => 50.0,
                'capacity_weight' => 500.0,
                'settings' => ['zona_picking' => true, 'acceso_rapido' => true],
            ],
            [
                'name' => 'Almacén de Uso Temporal',
                'code' => 'TEMP-01',
                'description' => 'Área de almacenamiento temporal para productos en tránsito',
                'location_type' => 'temporary',
                'aisle' => 'T',
                'capacity_volume' => 200.0,
                'capacity_weight' => 3000.0,
                'settings' => ['temporal' => true, 'uso_flexible' => true],
            ],
        ];
    }
}
