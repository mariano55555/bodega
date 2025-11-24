<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Company & User Relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            // Polymorphic relationship (can attach to Purchase, Dispatch, Transfer, etc.)
            $table->morphs('documentable');

            // Document Information
            $table->string('document_type', 50); // invoice, receipt, ccf, delivery_note, photo, other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_number')->nullable(); // e.g., invoice number, CCF number

            // File Information
            $table->string('file_name'); // original filename
            $table->string('file_path'); // storage path
            $table->string('file_type', 50); // pdf, jpg, png, xlsx, etc.
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('disk', 50)->default('local'); // storage disk

            // Metadata
            $table->date('document_date')->nullable(); // date on the document
            $table->decimal('document_amount', 15, 2)->nullable(); // for invoices/receipts
            $table->string('issuer')->nullable(); // who issued the document
            $table->string('recipient')->nullable(); // who receives the document
            $table->json('metadata')->nullable(); // additional flexible data

            // Status & Versioning
            $table->string('status', 20)->default('active'); // active, archived, deleted
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('previous_version_id')->nullable()->constrained('documents')->nullOnDelete();

            // Access Control
            $table->boolean('is_public')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Audit Fields
            $table->timestamp('active_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (morphs already creates an index for documentable_type and documentable_id)
            $table->index(['company_id', 'document_type']);
            $table->index('document_date');
            $table->index('status');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
