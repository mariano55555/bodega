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
        Schema::table('movement_reasons', function (Blueprint $table) {
            $table->string('legacy_name')->nullable()->after('legacy_code')
                ->comment('Nombre del sistema anterior (COMPRAS LOCALES, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_reasons', function (Blueprint $table) {
            $table->dropColumn('legacy_name');
        });
    }
};
