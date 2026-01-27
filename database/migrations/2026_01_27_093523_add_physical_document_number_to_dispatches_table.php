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
            $table->string('physical_document_number', 100)->nullable()->after('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropColumn('physical_document_number');
        });
    }
};
