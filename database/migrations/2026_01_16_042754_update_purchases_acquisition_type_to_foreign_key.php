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
        // Simply add the foreign key constraint
        // The column already exists from a previous migration
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign('acquisition_type_id')->references('id')->on('acquisition_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['acquisition_type_id']);
        });
    }
};
