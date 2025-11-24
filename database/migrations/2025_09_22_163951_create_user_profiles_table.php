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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users'); // One profile per user
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');

            // Employee information
            $table->string('employee_id')->nullable(); // Company-specific employee ID
            $table->string('position')->nullable();
            $table->string('department')->nullable();

            // Contact information
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();

            // Address information
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('ES'); // Spain default
            $table->string('postal_code')->nullable();

            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Employment dates
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();

            // User-specific permissions
            $table->json('permissions')->nullable(); // Specific permissions beyond role

            // Status fields
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'branch_id']);
            $table->index(['user_id', 'company_id']);
            $table->index(['is_active', 'active_at']);
            $table->index('employee_id');
            $table->unique(['company_id', 'employee_id']); // Employee ID unique within company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
