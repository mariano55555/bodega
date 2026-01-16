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
        Schema::table('products', function (Blueprint $table) {
            // Make valuation_method nullable - cliente pidió quitar este campo temporalmente
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore valuation_method to non-nullable with default 'fifo'
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('fifo')->change();
        });
    }
};
