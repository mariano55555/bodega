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
        // Update settings JSON column to add employee code configuration
        DB::table('companies')->get()->each(function ($company) {
            $settings = json_decode($company->settings ?? '{}', true);

            // Add employee code settings if not already present
            if (! isset($settings['auto_generate_employee_code'])) {
                $settings['auto_generate_employee_code'] = false;
            }

            if (! isset($settings['employee_code_prefix'])) {
                $settings['employee_code_prefix'] = 'EMP';
            }

            if (! isset($settings['employee_code_padding'])) {
                $settings['employee_code_padding'] = 3;
            }

            DB::table('companies')
                ->where('id', $company->id)
                ->update(['settings' => json_encode($settings)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove employee code settings from companies
        DB::table('companies')->get()->each(function ($company) {
            $settings = json_decode($company->settings ?? '{}', true);

            unset($settings['auto_generate_employee_code']);
            unset($settings['employee_code_prefix']);
            unset($settings['employee_code_padding']);

            DB::table('companies')
                ->where('id', $company->id)
                ->update(['settings' => json_encode($settings)]);
        });
    }
};
