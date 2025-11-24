<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('model_id')->constrained('companies')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('company_id');
            $table->timestamp('active_at')->nullable()->after('is_active');
            $table->timestamp('assigned_at')->nullable()->after('active_at');
            $table->timestamp('expires_at')->nullable()->after('assigned_at');
            $table->foreignId('assigned_by')->nullable()->after('expires_at')->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('assigned_by')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Add indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['is_active', 'active_at']);
            $table->index(['expires_at']);
            $table->index(['assigned_by']);
        });

        // Update existing records to set active status
        DB::table('model_has_roles')->update([
            'is_active' => true,
            'active_at' => now(),
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);

            $table->dropIndex(['company_id', 'is_active']);
            $table->dropIndex(['is_active', 'active_at']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['assigned_by']);

            $table->dropColumn([
                'company_id',
                'is_active',
                'active_at',
                'assigned_at',
                'expires_at',
                'assigned_by',
                'created_by',
                'updated_by',
                'deleted_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);
        });
    }
};
