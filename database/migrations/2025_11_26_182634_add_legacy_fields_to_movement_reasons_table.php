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
            $table->string('legacy_code', 5)->nullable()->after('code')->index()
                ->comment('Código del sistema anterior (E0, S1, etc.)');
            $table->boolean('affects_cost')->default(true)->after('requires_documentation')
                ->comment('Si es false, no afecta costo promedio en Kardex');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_reasons', function (Blueprint $table) {
            $table->dropIndex(['legacy_code']);
            $table->dropColumn(['legacy_code', 'affects_cost']);
        });
    }
};
