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
        // Add 'combustible' to dispatch_type ENUM
        DB::statement("ALTER TABLE dispatches MODIFY COLUMN dispatch_type ENUM('venta','interno','externo','donacion','combustible') DEFAULT 'interno'");

        Schema::create('dispatch_fuel_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->unique()->constrained('dispatches')->onDelete('cascade');

            // Vehicle/Equipment information
            $table->string('vehicle_class')->nullable();
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_plate', 100)->nullable();
            $table->decimal('odometer_reading', 12, 2)->nullable();
            $table->decimal('horometer_reading', 12, 2)->nullable();

            // Mission/Justification information
            $table->string('place_to_visit', 500)->nullable();
            $table->text('mission_description')->nullable();
            $table->decimal('kilometers_to_travel', 10, 2)->nullable();

            // Active status
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_fuel_details');

        DB::statement("ALTER TABLE dispatches MODIFY COLUMN dispatch_type ENUM('venta','interno','externo','donacion') DEFAULT 'interno'");
    }
};
