<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database FOR ENA (Escuela Nacional de Agricultura).
     *
     * IMPORTANTE: Sistema preparado exclusivamente para la demo y capacitación de la ENA.
     * Este seeder crea SOLO los datos necesarios para la Escuela Nacional de Agricultura.
     *
     * Orden de ejecución:
     * 1. Unidades de medida
     * 2. Roles y permisos
     * 3. Empresa y sucursal ENA
     * 4. Bodegas ENA (1 general + 4 fraccionarias)
     * 5. Usuarios ENA (6 usuarios)
     *
     * Para ejecutar: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->command->info('🎓 Iniciando población de base de datos para ESCUELA NACIONAL DE AGRICULTURA (ENA)');
        $this->command->line('');

        // Step 1: Base data - Units of measure
        $this->command->info('📏 Paso 1: Creando unidades de medida...');
        $this->call(UnitsOfMeasureSeeder::class);
        $this->command->line('');

        // Step 2: Authentication system - Roles & Permissions
        $this->command->info('🔐 Paso 2: Configurando roles y permisos...');
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(VisorWarehouseAccessSeeder::class);
        $this->command->line('');

        // Step 3: ENA Company & Branch
        $this->command->info('🎓 Paso 3: Creando empresa ENA y campus...');
        $this->call(ENACompanySeeder::class);
        $this->command->line('');

        // Step 4: ENA Warehouses (1 general + 4 fractional)
        $this->command->info('🏢 Paso 4: Creando bodegas de la ENA...');
        $this->call(ENAWarehousesSeeder::class);
        $this->command->line('');

        // Step 5: ENA Users (6 users with roles)
        $this->command->info('👥 Paso 5: Creando usuarios de la ENA...');
        $this->call(ENAUsersSeeder::class);
        $this->command->line('');

        // Step 6: ENA Suppliers
        $this->command->info('🏪 Paso 6: Creando proveedores...');
        $this->call(ENASuppliersSeeder::class);
        $this->command->line('');

        // Step 7: ENA Donors
        $this->command->info('🤝 Paso 7: Creando donantes...');
        $this->call(ENADonorsSeeder::class);
        $this->command->line('');

        // Step 8: ENA Products (30 productos clave)
        $this->command->info('📦 Paso 8: Creando catálogo de productos...');
        $this->call(ENAProductsSeeder::class);
        $this->command->line('');

        // Step 9: ENA Demo Data (compras, traslados, despachos, donaciones, ajustes)
        $this->command->info('🎬 Paso 9: Creando datos de demostración...');
        $this->call(ENADemoDataSeeder::class);
        $this->command->line('');

        $this->command->info('✅ Base de datos ENA poblada exitosamente con datos de demostración');
        $this->command->line('');
        $this->command->info('📊 Resumen de datos creados:');
        $this->command->line('   • 24 unidades de medida');
        $this->command->line('   • Roles del sistema (super-admin, company-admin, warehouse-manager, warehouse-operator)');
        $this->command->line('   • 1 empresa: Escuela Nacional de Agricultura');
        $this->command->line('   • 1 sucursal: Campus Central Santa Tecla');
        $this->command->line('   • 5 bodegas:');
        $this->command->line('      - 1 Bodega General (500 m³)');
        $this->command->line('      - 4 Bodegas Fraccionarias (230 m³ total)');
        $this->command->line('   • 6 usuarios con roles asignados:');
        $this->command->line('      - 1 Super Admin (IT)');
        $this->command->line('      - 1 Company Admin (Jefe Almacén General)');
        $this->command->line('      - 4 Warehouse Managers/Operators (Coordinadores)');
        $this->command->line('   • 5 proveedores estratégicos');
        $this->command->line('   • 6 donantes (FAO, USAID, AECID, BID, FUSADES, MAG)');
        $this->command->line('   • 30 productos en 5 categorías');
        $this->command->line('   • Datos de demostración completos:');
        $this->command->line('      - Compra inicial (50 sacos fertilizante)');
        $this->command->line('      - Traslado General → Cultivos (10 sacos)');
        $this->command->line('      - Despacho interno Cultivos (2 sacos)');
        $this->command->line('      - Traslado entre fraccionarias (5 palas)');
        $this->command->line('      - Donación FAO (200 kg semilla maíz)');
        $this->command->line('      - Ajuste por vencimiento (2 kg levadura)');
        $this->command->line('');
        $this->command->info('🔐 Credenciales de acceso:');
        $this->command->line('   Super Admin: admin@ena.gob.sv / password');
        $this->command->line('   Jefe Almacén General: almacen.general@ena.gob.sv / password');
        $this->command->line('   Coordinador Cultivos: cultivos@ena.gob.sv / password');
        $this->command->line('   Coordinador Pecuario: pecuaria@ena.gob.sv / password');
        $this->command->line('   Coordinador Procesamiento: procesamiento@ena.gob.sv / password');
        $this->command->line('   Jefe Mantenimiento: mantenimiento@ena.gob.sv / password');
        $this->command->line('');
        $this->command->info('🌐 Sistema configurado para El Salvador:');
        $this->command->line('   • Cliente: Escuela Nacional de Agricultura "Roberto Quiñónez"');
        $this->command->line('   • Ministerio: MAG (Ministerio de Agricultura y Ganadería)');
        $this->command->line('   • Ubicación: Santa Tecla, La Libertad');
        $this->command->line('   • Moneda: Dólar Estadounidense (USD)');
        $this->command->line('   • Idioma: Español');
        $this->command->line('');
        $this->command->info('🎯 Sistema listo para demo y capacitación ENA');
    }
}
