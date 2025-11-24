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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('slug'); // Unique within company scope
            $table->string('code', 20)->unique(); // For external integrations
            $table->text('description')->nullable();

            // Address fields
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('ES'); // Spain default
            $table->string('postal_code')->nullable();

            // Management
            $table->foreignId('manager_id')->nullable()->constrained('users');

            // Branch settings
            $table->json('settings')->nullable(); // Branch-specific settings

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
            $table->index(['company_id', 'is_active', 'active_at']);
            $table->index('manager_id');
            $table->index('code');
            $table->unique(['company_id', 'slug']); // Slug unique within company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
