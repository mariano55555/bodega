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
            $table->enum('acquisition_type', ['normal', 'convenio', 'proyecto', 'otro'])
                ->default('normal')
                ->after('payment_method');
            $table->string('project_name')->nullable()->after('acquisition_type');
            $table->string('agreement_number')->nullable()->after('project_name');
            $table->boolean('is_retroactive')->default(false)->after('agreement_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['acquisition_type', 'project_name', 'agreement_number', 'is_retroactive']);
        });
    }
};
