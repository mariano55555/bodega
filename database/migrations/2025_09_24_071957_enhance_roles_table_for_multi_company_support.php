<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->after('name')->index();
            $table->text('description')->nullable()->after('slug');
            $table->unsignedBigInteger('company_id')->nullable()->after('description');
            $table->json('permissions')->nullable()->after('company_id');
            $table->integer('level')->default(0)->after('permissions');
            $table->boolean('is_active')->default(true)->after('level');
            $table->timestamp('active_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('created_by')->nullable()->after('active_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            $table->softDeletes();

            // Add foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();

            // Add composite indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['is_active', 'active_at']);
            $table->index(['level', 'is_active']);
        });

        // Update existing roles to have slugs
        DB::table('roles')->get()->each(function ($role) {
            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'slug' => Str::slug($role->name),
                    'active_at' => now(),
                ]);
        });

        // Make slug unique within company scope
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['slug', 'company_id', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);

            $table->dropIndex(['company_id', 'is_active']);
            $table->dropIndex(['is_active', 'active_at']);
            $table->dropIndex(['level', 'is_active']);
            $table->dropIndex(['slug']);
            $table->dropUnique(['slug', 'company_id', 'guard_name']);

            $table->dropColumn([
                'slug',
                'description',
                'company_id',
                'permissions',
                'level',
                'is_active',
                'active_at',
                'created_by',
                'updated_by',
                'deleted_by',
                'deleted_at',
            ]);
        });
    }
};
