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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('ES'); // Spain default
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('total_capacity', 12, 2)->nullable(); // Total storage capacity
            $table->string('capacity_unit', 20)->default('m3'); // m3, pallets, etc.
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Operating hours and settings
            $table->json('operating_hours')->nullable(); // Store operating hours
            $table->json('settings')->nullable(); // Warehouse-specific settings

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'active_at']);
            $table->index('code');
            $table->index('manager_id');
            $table->index(['city', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
