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
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('name');
            $table->string('slug'); // Unique within warehouse scope
            $table->string('code', 20); // Unique within warehouse scope
            $table->enum('type', ['aisle', 'shelf', 'bin', 'zone', 'dock', 'staging'])->default('bin');
            $table->text('description')->nullable();

            // Hierarchical location structure
            $table->string('section')->nullable(); // Section A, B, C
            $table->string('aisle')->nullable(); // Aisle 1, 2, 3
            $table->string('shelf')->nullable(); // Shelf A, B, C
            $table->string('bin')->nullable(); // Bin 1, 2, 3
            $table->string('location_path')->nullable(); // Auto-generated: section/aisle/shelf/bin

            // Storage capacity
            $table->decimal('capacity', 12, 4)->nullable(); // Storage capacity
            $table->foreignId('capacity_unit_id')->nullable()->constrained('units_of_measure');

            // Physical attributes
            $table->decimal('length', 8, 2)->nullable(); // In centimeters
            $table->decimal('width', 8, 2)->nullable(); // In centimeters
            $table->decimal('height', 8, 2)->nullable(); // In centimeters
            $table->decimal('weight_limit', 10, 2)->nullable(); // In kilograms

            // Barcode for scanning operations
            $table->string('barcode')->nullable();

            // Status fields
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['warehouse_id', 'is_active', 'active_at']);
            $table->index('type');
            $table->index('location_path');
            $table->index('barcode');
            $table->unique(['warehouse_id', 'slug']); // Slug unique within warehouse
            $table->unique(['warehouse_id', 'code']); // Code unique within warehouse
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily to allow dropping
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('storage_locations');
        Schema::enableForeignKeyConstraints();
    }
};
