<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates roles, permissions, and users for El Salvador warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando usuarios del sistema...');

        // Note: Roles and permissions are already created by RolesAndPermissionsSeeder
        // No need to create them again

        // Create users
        $this->createUsers();

        $this->command->info('✓ Usuarios creados exitosamente');
    }

    private function createRoles(): void
    {
        $this->command->line('- Creando roles del sistema...');

        $roles = [
            [
                'name' => 'super_admin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'company_admin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'branch-manager',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse-manager',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse-operator',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }

    private function createPermissions(): void
    {
        $this->command->line('- Creando permisos del sistema...');

        $permissions = [
            // Company management
            'companies.view',
            'companies.create',
            'companies.edit',
            'companies.delete',

            // Branch management
            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',

            // Warehouse management
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',

            // Storage location management
            'storage_locations.view',
            'storage_locations.create',
            'storage_locations.edit',
            'storage_locations.delete',

            // Product management
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Inventory management
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
            'inventory.move',
            'inventory.transfer',

            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Supplier management
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',

            // Customer management
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Reports
            'reports.view',
            'reports.generate',
            'reports.export',

            // System administration
            'system.settings',
            'system.logs',
            'system.backup',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    private function assignPermissions(): void
    {
        $this->command->line('- Asignando permisos a roles...');

        // Super Admin gets all permissions
        $superAdmin = Role::findByName('super_admin');
        $superAdmin->givePermissionTo(Permission::all());

        // Company Admin permissions
        $companyAdmin = Role::findByName('company_admin');
        $companyAdmin->givePermissionTo([
            'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
            'storage_locations.view', 'storage_locations.create', 'storage_locations.edit', 'storage_locations.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete', 'inventory.move', 'inventory.transfer',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'reports.view', 'reports.generate', 'reports.export',
        ]);

        // Branch Manager permissions
        $branchManager = Role::findByName('branch-manager');
        $branchManager->givePermissionTo([
            'branches.view',
            'warehouses.view', 'warehouses.edit',
            'storage_locations.view', 'storage_locations.create', 'storage_locations.edit',
            'products.view', 'products.create', 'products.edit',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.move', 'inventory.transfer',
            'users.view', 'users.create', 'users.edit',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'customers.view', 'customers.create', 'customers.edit',
            'reports.view', 'reports.generate',
        ]);

        // Warehouse Manager permissions
        $warehouseManager = Role::findByName('warehouse-manager');
        $warehouseManager->givePermissionTo([
            'warehouses.view',
            'storage_locations.view', 'storage_locations.create', 'storage_locations.edit',
            'products.view',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.move', 'inventory.transfer',
            'users.view',
            'suppliers.view',
            'customers.view',
            'reports.view', 'reports.generate',
        ]);

        // Warehouse Operator permissions
        $warehouseOperator = Role::findByName('warehouse-operator');
        $warehouseOperator->givePermissionTo([
            'warehouses.view',
            'storage_locations.view',
            'products.view',
            'inventory.view', 'inventory.edit', 'inventory.move',
            'suppliers.view',
            'customers.view',
        ]);
    }

    private function createUsers(): void
    {
        $this->command->line('- Creando usuarios del sistema...');

        // Get companies
        $companies = DB::table('companies')->get()->keyBy('slug');

        // Super Admin (system-wide access)
        $superAdmin = User::create([
            'name' => 'Administrador del Sistema',
            'email' => 'admin@sistema.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'company_id' => null,
            'branch_id' => null,
        ]);
        $superAdmin->assignRole('super-admin');

        // Company administrators
        $companyAdmins = [
            [
                'name' => 'María Elena Rodríguez',
                'email' => 'admin@lacadena.com.do',
                'company_slug' => 'supermercados-la-cadena',
            ],
            [
                'name' => 'Carlos Antonio Pérez',
                'email' => 'admin@dcentralrd.com',
                'company_slug' => 'distribuidora-central-rd',
            ],
            [
                'name' => 'Ana Patricia García',
                'email' => 'admin@gid.com.do',
                'company_slug' => 'grupo-industrial-dominicano',
            ],
            [
                'name' => 'Roberto José Jiménez',
                'email' => 'admin@comercialcaribe.com',
                'company_slug' => 'comercial-del-caribe',
            ],
            [
                'name' => 'Carmen Luz Morales',
                'email' => 'admin@agroestedr.com',
                'company_slug' => 'agropecuaria-del-este',
            ],
        ];

        foreach ($companyAdmins as $adminData) {
            $company = $companies->get($adminData['company_slug']);
            if ($company) {
                $user = User::create([
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'branch_id' => null,
                ]);
                $user->assignRole('company-admin');
            }
        }

        // Sample branch managers, warehouse managers, and operators
        $sampleUsers = [
            // Supermercados La Cadena
            [
                'name' => 'José Manuel Fernández',
                'email' => 'manager.santodomingo@lacadena.com.do',
                'role' => 'branch-manager',
                'company_slug' => 'supermercados-la-cadena',
            ],
            [
                'name' => 'Luisa María Santos',
                'email' => 'almacen.central@lacadena.com.do',
                'role' => 'warehouse-manager',
                'company_slug' => 'supermercados-la-cadena',
            ],
            [
                'name' => 'Miguel Ángel Castillo',
                'email' => 'operador1@lacadena.com.do',
                'role' => 'warehouse-operator',
                'company_slug' => 'supermercados-la-cadena',
            ],
            [
                'name' => 'Rosa Elena Vásquez',
                'email' => 'operador2@lacadena.com.do',
                'role' => 'warehouse-operator',
                'company_slug' => 'supermercados-la-cadena',
            ],

            // Distribuidora Central RD
            [
                'name' => 'Rafael Eduardo Núñez',
                'email' => 'manager.santiago@dcentralrd.com',
                'role' => 'branch-manager',
                'company_slug' => 'distribuidora-central-rd',
            ],
            [
                'name' => 'Teresa Isabel Reyes',
                'email' => 'almacen.norte@dcentralrd.com',
                'role' => 'warehouse-manager',
                'company_slug' => 'distribuidora-central-rd',
            ],
            [
                'name' => 'Pedro Luis Herrera',
                'email' => 'operador1@dcentralrd.com',
                'role' => 'warehouse-operator',
                'company_slug' => 'distribuidora-central-rd',
            ],

            // Grupo Industrial Dominicano
            [
                'name' => 'Alejandro José Medina',
                'email' => 'manager.produccion@gid.com.do',
                'role' => 'branch-manager',
                'company_slug' => 'grupo-industrial-dominicano',
            ],
            [
                'name' => 'Gloria Patricia Ramos',
                'email' => 'almacen.materiaprima@gid.com.do',
                'role' => 'warehouse-manager',
                'company_slug' => 'grupo-industrial-dominicano',
            ],
            [
                'name' => 'Francisco Javier Díaz',
                'email' => 'operador1@gid.com.do',
                'role' => 'warehouse-operator',
                'company_slug' => 'grupo-industrial-dominicano',
            ],

            // Comercial del Caribe
            [
                'name' => 'Víctor Manuel Cruz',
                'email' => 'manager.caucedo@comercialcaribe.com',
                'role' => 'branch-manager',
                'company_slug' => 'comercial-del-caribe',
            ],
            [
                'name' => 'Maribel Concepción Torres',
                'email' => 'almacen.puerto@comercialcaribe.com',
                'role' => 'warehouse-manager',
                'company_slug' => 'comercial-del-caribe',
            ],
            [
                'name' => 'Esteban Arturo Mejía',
                'email' => 'operador1@comercialcaribe.com',
                'role' => 'warehouse-operator',
                'company_slug' => 'comercial-del-caribe',
            ],

            // Agropecuaria del Este
            [
                'name' => 'Silvia Margarita Pimentel',
                'email' => 'manager.laromana@agroestedr.com',
                'role' => 'branch-manager',
                'company_slug' => 'agropecuaria-del-este',
            ],
            [
                'name' => 'Antonio Ramón Guzmán',
                'email' => 'almacen.refrigerado@agroestedr.com',
                'role' => 'warehouse-manager',
                'company_slug' => 'agropecuaria-del-este',
            ],
            [
                'name' => 'Yolanda Mercedes Vargas',
                'email' => 'operador1@agroestedr.com',
                'role' => 'warehouse-operator',
                'company_slug' => 'agropecuaria-del-este',
            ],
        ];

        foreach ($sampleUsers as $userData) {
            $company = $companies->get($userData['company_slug']);
            if ($company) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'branch_id' => null, // Will be set when branches are created
                ]);
                $user->assignRole($userData['role']);
            }
        }

        $this->command->line('✓ Usuarios creados:');
        $this->command->line('  - 1 Super Administrador');
        $this->command->line('  - 5 Administradores de Empresa');
        $this->command->line('  - 5 Gerentes de Sucursal');
        $this->command->line('  - 5 Gerentes de Almacén');
        $this->command->line('  - 6 Operadores de Almacén');
        $this->command->line('  - Total: 22 usuarios');
    }
}
