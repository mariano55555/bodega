<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Copiar contact_position a position
            DB::update('UPDATE customers SET position = contact_position WHERE contact_position IS NOT NULL');

            // Consolidar contact_name a name si name está vacío
            DB::update('UPDATE customers SET name = contact_name WHERE name IS NULL AND contact_name IS NOT NULL');

            // Consolidar contact_email a email si email está vacío
            DB::update('UPDATE customers SET email = contact_email WHERE email IS NULL AND contact_email IS NOT NULL');

            // Consolidar contact_phone a phone si phone está vacío
            DB::update('UPDATE customers SET phone = contact_phone WHERE phone IS NULL AND contact_phone IS NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir la migración de datos
    }
};
