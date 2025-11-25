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
        Schema::table('dte_imports', function (Blueprint $table) {
            // Remove the unique constraint on codigo_generacion to allow soft-deleted records with same code
            $table->dropUnique(['codigo_generacion']);

            // Add a regular index for performance (not unique)
            $table->index('codigo_generacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dte_imports', function (Blueprint $table) {
            $table->dropIndex(['codigo_generacion']);
            $table->unique('codigo_generacion');
        });
    }
};
