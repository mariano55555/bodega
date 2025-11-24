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
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('abbreviation', 10)->unique(); // kg, lb, pcs, m3, etc.
            $table->enum('type', ['weight', 'volume', 'length', 'quantity', 'area', 'time'])->default('quantity');
            $table->text('description')->nullable();

            // Conversion system
            $table->foreignId('base_unit_id')->nullable()->constrained('units_of_measure'); // Reference to base unit for conversions
            $table->decimal('conversion_factor', 15, 6)->default(1.000000); // Multiplier to convert to base unit
            $table->integer('precision')->default(2); // Decimal places for this unit

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
            $table->index(['type', 'is_active']);
            $table->index('base_unit_id');
            $table->index(['is_active', 'active_at']);
            $table->index('abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};
