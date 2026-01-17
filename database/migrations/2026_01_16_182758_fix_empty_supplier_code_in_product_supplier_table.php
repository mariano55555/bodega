<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, make supplier_code nullable temporarily
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->string('supplier_code', 100)->nullable()->change();
        });

        // Generate automatic codes for empty supplier_code values
        $emptyRecords = DB::table('product_supplier')
            ->where('supplier_code', '')
            ->orWhereNull('supplier_code')
            ->get(['id', 'product_id']);

        foreach ($emptyRecords as $record) {
            $autoCode = 'AUTO-'.strtoupper(substr(md5($record->id.$record->product_id.time()), 0, 8));

            DB::table('product_supplier')
                ->where('id', $record->id)
                ->update(['supplier_code' => $autoCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove auto-generated codes
        DB::table('product_supplier')
            ->where('supplier_code', 'like', 'AUTO-%')
            ->update(['supplier_code' => '']);
    }
};
