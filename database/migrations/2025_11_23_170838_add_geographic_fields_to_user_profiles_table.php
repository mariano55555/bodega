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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Add geographic foreign keys after address
            $table->foreignId('departamento_id')->nullable()->after('address')->constrained('departamentos')->nullOnDelete();
            $table->foreignId('ciudad_id')->nullable()->after('departamento_id')->constrained('ciudades')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['ciudad_id']);
            $table->dropForeign(['departamento_id']);
            $table->dropColumn(['departamento_id', 'ciudad_id']);
        });
    }
};
