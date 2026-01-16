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
        // First, temporarily change to string to allow data update
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_type')->default('credito')->change();
        });

        // Update existing 'efectivo' values to 'contado'
        DB::table('purchases')
            ->where('purchase_type', 'efectivo')
            ->update(['purchase_type' => 'contado']);

        // Now change back to enum with new values
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('purchase_type', ['contado', 'credito'])->default('credito')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, temporarily change to string to allow data update
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_type')->default('efectivo')->change();
        });

        // Restore 'contado' values back to 'efectivo'
        DB::table('purchases')
            ->where('purchase_type', 'contado')
            ->update(['purchase_type' => 'efectivo']);

        // Now change back to enum with original values
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('purchase_type', ['efectivo', 'credito'])->default('efectivo')->change();
        });
    }
};
