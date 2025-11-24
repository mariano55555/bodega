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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();

            // Event information
            $table->string('event_type', 100); // login, logout, failed_login, password_change, permission_denied, etc.
            $table->string('severity', 20)->default('info'); // info, warning, error, critical
            $table->text('description');
            $table->json('metadata')->nullable(); // Additional context data

            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, etc.
            $table->string('url')->nullable();
            $table->integer('status_code')->nullable();

            // Location information
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();

            // Related entities
            $table->string('affected_model_type')->nullable();
            $table->unsignedBigInteger('affected_model_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index('severity');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
