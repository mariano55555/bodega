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
        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->enum('alert_type', [
                'low_stock',        // Below minimum threshold
                'high_stock',       // Above maximum threshold
                'zero_stock',       // Out of stock
                'negative_stock',   // Negative inventory
                'expiring_soon',    // Items expiring within threshold
                'expired',          // Already expired items
                'overdue_count',    // Physical count overdue
                'discrepancy',       // Count vs system discrepancy
            ]);
            $table->decimal('threshold_value', 12, 4)->nullable(); // Alert threshold quantity
            $table->decimal('current_value', 12, 4)->nullable(); // Current quantity triggering alert
            $table->string('priority', 20)->default('medium'); // low, medium, high, critical
            $table->text('message')->nullable(); // Alert description
            $table->json('metadata')->nullable(); // Additional alert data

            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->text('acknowledgment_notes')->nullable();

            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();

            // Auto-resolution settings
            $table->boolean('auto_resolve')->default(false); // Auto-resolve when condition no longer met
            $table->timestamp('expires_at')->nullable(); // Alert expiration

            // Notification settings
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();
            $table->json('notification_log')->nullable(); // Log of sent notifications

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index('alert_type');
            $table->index('priority');
            $table->index(['is_active', 'active_at']);
            $table->index(['is_acknowledged', 'acknowledged_at']);
            $table->index(['is_resolved', 'resolved_at']);
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
    }
};
