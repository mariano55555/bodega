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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->after('area_id');
            $table->string('position')->nullable()->after('employee_code');

            // Unique constraint por company
            $table->unique(['company_id', 'employee_code'], 'unique_employee_code_per_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('unique_employee_code_per_company');
            $table->dropColumn(['employee_code', 'position']);
        });
    }
};
