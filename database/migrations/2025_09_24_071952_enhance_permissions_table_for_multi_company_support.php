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
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('slug')->after('name')->index();
            $table->text('description')->nullable()->after('slug');
            $table->string('group')->after('description')->default('general')->index();
            $table->json('metadata')->nullable()->after('group');
            $table->boolean('is_active')->default(true)->after('metadata');
            $table->timestamp('active_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('created_by')->nullable()->after('active_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            $table->softDeletes();

            // Add foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();

            // Add composite indexes
            $table->index(['group', 'is_active']);
            $table->index(['is_active', 'active_at']);
        });

        // Update existing permissions to have slugs
        DB::table('permissions')->get()->each(function ($permission) {
            DB::table('permissions')
                ->where('id', $permission->id)
                ->update([
                    'slug' => Str::slug($permission->name),
                    'active_at' => now(),
                ]);
        });

        // Make slug unique after populating
        Schema::table('permissions', function (Blueprint $table) {
            $table->unique(['slug', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);

            $table->dropIndex(['group', 'is_active']);
            $table->dropIndex(['is_active', 'active_at']);
            $table->dropIndex(['slug']);
            $table->dropUnique(['slug', 'guard_name']);

            $table->dropColumn([
                'slug',
                'description',
                'group',
                'metadata',
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
