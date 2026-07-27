<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unused, always-null `permissions` JSON column shadowed Spatie's
     * `permissions()` relationship on the Role model, breaking syncPermissions()
     * and givePermissionTo(). Dropping it lets role permission management work.
     */
    public function up(): void
    {
        if (Schema::hasColumn('roles', 'permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('roles', 'permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('description');
            });
        }
    }
};
