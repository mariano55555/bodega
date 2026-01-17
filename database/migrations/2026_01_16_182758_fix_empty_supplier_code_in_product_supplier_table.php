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
        // Convert empty supplier_code strings to NULL to avoid unique constraint violations
        DB::table('product_supplier')
            ->where('supplier_code', '')
            ->update(['supplier_code' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - NULL values are acceptable
    }
};
