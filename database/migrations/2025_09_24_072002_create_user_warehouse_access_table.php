<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_warehouse_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('access_type')->default('full'); // full, read_only, restricted
            $table->json('permissions')->nullable(); // specific permissions for this warehouse
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for performance
            $table->index(['user_id', 'company_id', 'is_active']);
            $table->index(['warehouse_id', 'is_active']);
            $table->index(['company_id', 'access_type', 'is_active']);
            $table->index(['is_active', 'active_at']);
            $table->index(['expires_at']);

            // Ensure no duplicate access entries
            $table->unique(['user_id', 'warehouse_id'], 'unique_user_warehouse_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_warehouse_access');
    }
};
