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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Add name fields after user_id
            $table->string('first_name')->nullable()->after('branch_id');
            $table->string('last_name')->nullable()->after('first_name');

            // Add additional profile fields that the model expects
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->string('job_title')->nullable()->after('department');
            $table->foreignId('manager_id')->nullable()->after('job_title')->constrained('users')->nullOnDelete();
            $table->decimal('salary', 10, 2)->nullable()->after('hire_date');
            $table->string('employment_type', 50)->nullable()->after('salary');

            // Add emergency contact relationship
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');

            // Add preferences
            $table->string('timezone', 50)->nullable()->after('emergency_contact_relationship');
            $table->string('language', 10)->nullable()->after('timezone');
            $table->string('date_format', 20)->nullable()->after('language');
            $table->string('time_format', 20)->nullable()->after('date_format');

            // Add profile extras
            $table->string('avatar_path')->nullable()->after('time_format');
            $table->json('skills')->nullable()->after('avatar_path');
            $table->json('certifications')->nullable()->after('skills');
            $table->json('settings')->nullable()->after('certifications');
            $table->text('bio')->nullable()->after('settings');
            $table->text('notes')->nullable()->after('bio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'date_of_birth',
                'gender',
                'job_title',
                'manager_id',
                'salary',
                'employment_type',
                'emergency_contact_relationship',
                'timezone',
                'language',
                'date_format',
                'time_format',
                'avatar_path',
                'skills',
                'certifications',
                'settings',
                'bio',
                'notes',
            ]);
        });
    }
};
