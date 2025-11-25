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
            // Remove the unique constraint on slug to allow soft-deleted records with same slug
            $table->dropUnique(['slug']);

            // Add a regular index for performance (not unique)
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dte_imports', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->unique('slug');
        });
    }
};
