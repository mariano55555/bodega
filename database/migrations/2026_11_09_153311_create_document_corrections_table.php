<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail of corrections applied to transfers, dispatches and internal productions
     * that had already moved stock.
     */
    public function up(): void
    {
        Schema::create('document_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('correctable');
            $table->text('reason');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('movement_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_corrections');
    }
};
