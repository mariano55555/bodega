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
        // Convert empty barcode strings to NULL to avoid unique constraint violations
        DB::table('products')
            ->where('barcode', '')
            ->update(['barcode' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - NULL values are acceptable
    }
};
