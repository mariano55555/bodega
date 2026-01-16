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
        Schema::table('purchases', function (Blueprint $table) {
            // Drop the old string column
            $table->dropColumn('fund_source');
        });

        Schema::table('purchases', function (Blueprint $table) {
            // Add new foreign key column
            $table->foreignId('fund_source_id')->nullable()->after('payment_method')->constrained('fund_sources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['fund_source_id']);
            $table->dropColumn('fund_source_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('fund_source')->nullable();
        });
    }
};
