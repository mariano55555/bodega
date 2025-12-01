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
        Schema::table('product_categories', function (Blueprint $table) {
            // Relación jerárquica (auto-referencial)
            $table->foreignId('parent_id')->nullable()->after('company_id')
                ->constrained('product_categories')->nullOnDelete();

            // Código legacy del cliente (54, 54101, etc.)
            $table->string('legacy_code', 10)->nullable()->after('code')
                ->comment('Código del sistema anterior del cliente');

            // Índices
            $table->index('legacy_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['legacy_code']);
            $table->dropColumn(['parent_id', 'legacy_code']);
        });
    }
};
