<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ENAUsersSeeder extends Seeder
{
    /**
     * Seed ENA users with proper roles and warehouse assignments.
     */
    public function run(): void
    {
        $this->command->info('👥 Creando usuarios de la ENA...');

        $company = DB::table('companies')->where('slug', 'escuela-nacional-agricultura')->first();

        if (! $company) {
            $this->command->error('❌ Error: Empresa ENA no encontrada');

            return;
        }

        // Get roles
        $superAdminRole = DB::table('roles')->where('name', 'super-admin')->first();
        $companyAdminRole = DB::table('roles')->where('name', 'company-admin')->first();
        $warehouseManagerRole = DB::table('roles')->where('name', 'warehouse-manager')->first();
        $warehouseOperatorRole = DB::table('roles')->where('name', 'warehouse-operator')->first();

        // 1. SUPER ADMIN - Departamento IT
        $superAdminId = DB::table('users')->insertGetId([
            'name' => 'Administrador IT ENA',
            'email' => 'admin@ena.gob.sv',
            'password' => Hash::make('password'),
            'company_id' => null, // Super admin no está ligado a empresa específica
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign super-admin role
        if ($superAdminRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $superAdminRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $superAdminId,
            ]);
        }

        $this->command->line("✓ Super Admin creado: admin@ena.gob.sv (ID: {$superAdminId})");

        // 2. JEFE DE ALMACÉN GENERAL - Admin de Bodega Central
        $almacenGeneralId = DB::table('users')->insertGetId([
            'name' => 'Carlos Méndez - Jefe de Almacén General',
            'email' => 'almacen.general@ena.gob.sv',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign company-admin role
        if ($companyAdminRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $companyAdminRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $almacenGeneralId,
            ]);
        }

        // Assign access to Bodega Central
        $bodegaCentral = DB::table('warehouses')->where('code', 'ENA-BG-001')->first();
        if ($bodegaCentral) {
            DB::table('user_warehouse_access')->insert([
                'user_id' => $almacenGeneralId,
                'warehouse_id' => $bodegaCentral->id,
                'company_id' => $company->id,
                'access_type' => 'full',
                'is_active' => true,
                'active_at' => now(),
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update warehouse manager
            DB::table('warehouses')->where('id', $bodegaCentral->id)->update([
                'manager_id' => $almacenGeneralId,
            ]);
        }

        $this->command->line("✓ Jefe Almacén General creado: almacen.general@ena.gob.sv (ID: {$almacenGeneralId})");

        // 3. COORDINADOR DE CULTIVOS
        $cultivosId = DB::table('users')->insertGetId([
            'name' => 'María González - Coordinadora de Cultivos',
            'email' => 'cultivos@ena.gob.sv',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign warehouse-manager role
        if ($warehouseManagerRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $warehouseManagerRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $cultivosId,
            ]);
        }

        // Assign access to Bodega Cultivos
        $bodegaCultivos = DB::table('warehouses')->where('code', 'ENA-BF-CULTIVOS')->first();
        if ($bodegaCultivos) {
            DB::table('user_warehouse_access')->insert([
                'user_id' => $cultivosId,
                'warehouse_id' => $bodegaCultivos->id,
                'company_id' => $company->id,
                'access_type' => 'full',
                'is_active' => true,
                'active_at' => now(),
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouses')->where('id', $bodegaCultivos->id)->update([
                'manager_id' => $cultivosId,
            ]);
        }

        $this->command->line("✓ Coordinador Cultivos creado: cultivos@ena.gob.sv (ID: {$cultivosId})");

        // 4. COORDINADOR PECUARIO
        $pecuariaId = DB::table('users')->insertGetId([
            'name' => 'José Ramírez - Coordinador Pecuario',
            'email' => 'pecuaria@ena.gob.sv',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($warehouseManagerRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $warehouseManagerRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $pecuariaId,
            ]);
        }

        $bodegaPecuaria = DB::table('warehouses')->where('code', 'ENA-BF-PECUARIA')->first();
        if ($bodegaPecuaria) {
            DB::table('user_warehouse_access')->insert([
                'user_id' => $pecuariaId,
                'warehouse_id' => $bodegaPecuaria->id,
                'company_id' => $company->id,
                'access_type' => 'full',
                'is_active' => true,
                'active_at' => now(),
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouses')->where('id', $bodegaPecuaria->id)->update([
                'manager_id' => $pecuariaId,
            ]);
        }

        $this->command->line("✓ Coordinador Pecuario creado: pecuaria@ena.gob.sv (ID: {$pecuariaId})");

        // 5. COORDINADOR DE PROCESAMIENTO
        $procesamientoId = DB::table('users')->insertGetId([
            'name' => 'Ana Martínez - Coordinadora de Agroindustria',
            'email' => 'procesamiento@ena.gob.sv',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($warehouseManagerRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $warehouseManagerRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $procesamientoId,
            ]);
        }

        $bodegaProceso = DB::table('warehouses')->where('code', 'ENA-BF-PROCESO')->first();
        if ($bodegaProceso) {
            DB::table('user_warehouse_access')->insert([
                'user_id' => $procesamientoId,
                'warehouse_id' => $bodegaProceso->id,
                'company_id' => $company->id,
                'access_type' => 'full',
                'is_active' => true,
                'active_at' => now(),
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouses')->where('id', $bodegaProceso->id)->update([
                'manager_id' => $procesamientoId,
            ]);
        }

        $this->command->line("✓ Coordinador Procesamiento creado: procesamiento@ena.gob.sv (ID: {$procesamientoId})");

        // 6. JEFE DE MANTENIMIENTO
        $mantenimientoId = DB::table('users')->insertGetId([
            'name' => 'Roberto Silva - Jefe de Mantenimiento',
            'email' => 'mantenimiento@ena.gob.sv',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($warehouseOperatorRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $warehouseOperatorRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $mantenimientoId,
            ]);
        }

        $bodegaMant = DB::table('warehouses')->where('code', 'ENA-BF-MANT')->first();
        if ($bodegaMant) {
            DB::table('user_warehouse_access')->insert([
                'user_id' => $mantenimientoId,
                'warehouse_id' => $bodegaMant->id,
                'company_id' => $company->id,
                'access_type' => 'full',
                'is_active' => true,
                'active_at' => now(),
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('warehouses')->where('id', $bodegaMant->id)->update([
                'manager_id' => $mantenimientoId,
            ]);
        }

        $this->command->line("✓ Jefe Mantenimiento creado: mantenimiento@ena.gob.sv (ID: {$mantenimientoId})");

        $this->command->info('✅ 6 usuarios ENA creados exitosamente');
        $this->command->line('   - 1 Super Admin (IT)');
        $this->command->line('   - 1 Company Admin (Jefe Almacén General)');
        $this->command->line('   - 4 Warehouse Managers/Operators (Coordinadores)');
        $this->command->line('');
        $this->command->line('   📧 Todos los usuarios tienen password: password');
    }
}
