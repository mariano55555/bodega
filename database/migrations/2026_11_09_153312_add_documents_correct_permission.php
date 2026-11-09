<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permission to correct transfers, dispatches and internal productions that already moved stock.
     * Super admins have it implicitly; it is meant to be assigned to roles such as "Editor Manager".
     */
    public function up(): void
    {
        Permission::query()->firstOrCreate(
            ['name' => 'documents.correct', 'guard_name' => 'web'],
            [
                'group' => 'documents',
                'description' => 'Corregir traslados, despachos y producciones internas que ya movieron inventario',
                'is_active' => true,
                'active_at' => now(),
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'documents.correct')->where('guard_name', 'web')->forceDelete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
