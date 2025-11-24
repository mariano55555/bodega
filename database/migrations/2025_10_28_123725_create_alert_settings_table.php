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
        Schema::create('alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();

            // Alert thresholds
            $table->integer('low_stock_threshold_days')->default(30)->comment('Days of stock remaining to trigger low stock alert');
            $table->decimal('critical_stock_percentage', 5, 2)->default(25.00)->comment('Percentage of minimum stock for critical priority');
            $table->decimal('high_stock_percentage', 5, 2)->default(50.00)->comment('Percentage of minimum stock for high priority');
            $table->decimal('medium_stock_percentage', 5, 2)->default(75.00)->comment('Percentage of minimum stock for medium priority');
            $table->integer('expiring_soon_days')->default(30)->comment('Days before expiration to trigger alert');
            $table->integer('expiring_critical_days')->default(7)->comment('Days for critical expiration alert');
            $table->integer('expiring_high_days')->default(15)->comment('Days for high priority expiration alert');

            // Email settings
            $table->boolean('email_alerts_enabled')->default(false)->comment('Enable/disable email alerts');
            $table->json('email_recipients')->nullable()->comment('Array of email addresses to receive alerts');
            $table->boolean('email_on_critical_only')->default(true)->comment('Send emails only for critical alerts');
            $table->boolean('email_on_low_stock')->default(true)->comment('Send emails for low stock alerts');
            $table->boolean('email_on_out_of_stock')->default(true)->comment('Send emails for out of stock alerts');
            $table->boolean('email_on_expiring')->default(true)->comment('Send emails for expiring products');
            $table->boolean('email_on_expired')->default(true)->comment('Send emails for expired products');
            $table->string('email_frequency')->default('immediate')->comment('immediate, daily_digest, weekly_digest');
            $table->time('digest_time')->nullable()->comment('Time to send digest emails');

            // Notification settings
            $table->boolean('browser_notifications_enabled')->default(true);
            $table->boolean('dashboard_alerts_enabled')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_settings');
    }
};
