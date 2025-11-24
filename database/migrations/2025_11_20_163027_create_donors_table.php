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
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('slug'); // Unique within company scope
            $table->string('legal_name')->nullable();
            $table->string('donor_type')->nullable(); // individual, organization, government, ngo, international
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address fields
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('SV'); // El Salvador default
            $table->string('postal_code')->nullable();

            // Contact information
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            // Donor specific fields
            $table->integer('rating')->nullable(); // 1-5 star rating
            $table->text('notes')->nullable();

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
            $table->index('donor_type');
            $table->index('rating');
            $table->index(['company_id', 'tax_id']);
            $table->unique(['company_id', 'slug']); // Slug unique within company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
