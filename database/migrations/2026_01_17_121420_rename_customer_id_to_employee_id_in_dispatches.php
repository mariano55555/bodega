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
        Schema::table('dispatches', function (Blueprint $table) {
            // Eliminar FK constraint antigua
            $table->dropForeign(['customer_id']);

            // Renombrar columna
            $table->renameColumn('customer_id', 'employee_id');

            // Recrear FK constraint
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            // Eliminar FK constraint
            $table->dropForeign(['employee_id']);

            // Renombrar columna de vuelta
            $table->renameColumn('employee_id', 'customer_id');

            // Recrear FK constraint antigua
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }
};
