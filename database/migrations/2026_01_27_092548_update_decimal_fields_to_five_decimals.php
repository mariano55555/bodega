<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates all decimal fields to support 5 decimal places for better precision.
     */
    public function up(): void
    {
        // Products table
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 15, 5)->nullable()->change();
            $table->decimal('price', 15, 5)->nullable()->change();
            $table->decimal('minimum_stock', 15, 5)->nullable()->default(0)->change();
            $table->decimal('maximum_stock', 15, 5)->nullable()->change();
        });

        // Inventory table
        Schema::table('inventory', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->default(0)->change();
            $table->decimal('reserved_quantity', 15, 5)->default(0)->change();
            $table->decimal('available_quantity', 15, 5)->default(0)->change();
            $table->decimal('unit_cost', 15, 5)->nullable()->change();
            $table->decimal('total_value', 15, 5)->default(0)->change();
            $table->decimal('last_count_quantity', 15, 5)->nullable()->change();
        });

        // Inventory movements table
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->change();
            $table->decimal('unit_cost', 15, 5)->nullable()->change();
            $table->decimal('total_cost', 15, 5)->nullable()->change();
            $table->decimal('previous_quantity', 15, 5)->nullable()->change();
            $table->decimal('new_quantity', 15, 5)->nullable()->change();
            $table->decimal('quantity_in', 15, 5)->default(0)->change();
            $table->decimal('quantity_out', 15, 5)->default(0)->change();
            $table->decimal('balance_quantity', 15, 5)->nullable()->change();
        });

        // Purchases table
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 5)->default(0)->change();
            $table->decimal('tax_amount', 15, 5)->default(0)->change();
            $table->decimal('discount_amount', 15, 5)->default(0)->change();
            $table->decimal('shipping_cost', 15, 5)->default(0)->change();
            $table->decimal('total', 15, 5)->default(0)->change();
        });

        // Purchase details table
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->change();
            $table->decimal('unit_cost', 15, 5)->change();
            $table->decimal('discount_amount', 15, 5)->default(0)->change();
            $table->decimal('tax_amount', 15, 5)->default(0)->change();
            $table->decimal('subtotal', 15, 5)->change();
            $table->decimal('total', 15, 5)->change();
        });

        // Dispatches table
        Schema::table('dispatches', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 5)->default(0)->change();
            $table->decimal('tax_amount', 15, 5)->default(0)->change();
            $table->decimal('discount_amount', 15, 5)->default(0)->change();
            $table->decimal('shipping_cost', 15, 5)->default(0)->change();
            $table->decimal('total', 15, 5)->default(0)->change();
        });

        // Dispatch details table
        Schema::table('dispatch_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->change();
            $table->decimal('quantity_dispatched', 15, 5)->default(0)->change();
            $table->decimal('quantity_delivered', 15, 5)->default(0)->change();
            $table->decimal('unit_price', 15, 5)->default(0)->change();
            $table->decimal('discount_amount', 15, 5)->default(0)->change();
            $table->decimal('tax_amount', 15, 5)->default(0)->change();
            $table->decimal('subtotal', 15, 5)->default(0)->change();
            $table->decimal('total', 15, 5)->default(0)->change();
        });

        // Inventory transfers table
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->decimal('shipping_cost', 15, 5)->nullable()->change();
        });

        // Inventory transfer details table
        Schema::table('inventory_transfer_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->change();
        });

        // Inventory adjustments table
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->change();
            $table->decimal('unit_cost', 15, 5)->nullable()->change();
            $table->decimal('total_value', 15, 5)->default(0)->change();
        });

        // Inventory alerts table
        Schema::table('inventory_alerts', function (Blueprint $table) {
            $table->decimal('threshold_value', 15, 5)->nullable()->change();
            $table->decimal('current_value', 15, 5)->nullable()->change();
        });

        // Product lots table
        Schema::table('product_lots', function (Blueprint $table) {
            $table->decimal('quantity_produced', 15, 5)->default(0)->change();
            $table->decimal('quantity_remaining', 15, 5)->default(0)->change();
            $table->decimal('unit_cost', 15, 5)->nullable()->change();
        });

        // Donations table
        Schema::table('donations', function (Blueprint $table) {
            $table->decimal('estimated_value', 15, 5)->default(0)->change();
            $table->decimal('tax_deduction_value', 15, 5)->nullable()->change();
        });

        // Donation details table
        Schema::table('donation_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 5)->change();
            $table->decimal('estimated_unit_value', 15, 5)->default(0)->change();
            $table->decimal('estimated_total_value', 15, 5)->default(0)->change();
        });

        // Storage locations table
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->decimal('capacity', 15, 5)->nullable()->change();
        });

        // Product supplier table
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->decimal('supplier_cost', 15, 5)->nullable()->change();
            $table->decimal('last_purchase_price', 15, 5)->nullable()->change();
        });

        // Inventory closures table
        Schema::table('inventory_closures', function (Blueprint $table) {
            $table->decimal('total_value', 15, 5)->default(0)->change();
            $table->decimal('total_quantity', 15, 5)->default(0)->change();
            $table->decimal('total_discrepancy_value', 15, 5)->default(0)->change();
        });

        // Inventory closure details table
        Schema::table('inventory_closure_details', function (Blueprint $table) {
            $table->decimal('opening_quantity', 15, 5)->default(0)->change();
            $table->decimal('opening_unit_cost', 15, 5)->default(0)->change();
            $table->decimal('opening_total_value', 15, 5)->default(0)->change();
            $table->decimal('quantity_in', 15, 5)->default(0)->change();
            $table->decimal('quantity_out', 15, 5)->default(0)->change();
            $table->decimal('calculated_closing_quantity', 15, 5)->default(0)->change();
            $table->decimal('calculated_closing_unit_cost', 15, 5)->default(0)->change();
            $table->decimal('calculated_closing_value', 15, 5)->default(0)->change();
            $table->decimal('physical_count_quantity', 15, 5)->nullable()->change();
            $table->decimal('physical_count_unit_cost', 15, 5)->nullable()->change();
            $table->decimal('physical_count_value', 15, 5)->nullable()->change();
            $table->decimal('discrepancy_quantity', 15, 5)->default(0)->change();
            $table->decimal('discrepancy_value', 15, 5)->default(0)->change();
            $table->decimal('adjusted_closing_quantity', 15, 5)->default(0)->change();
            $table->decimal('adjusted_closing_unit_cost', 15, 5)->default(0)->change();
            $table->decimal('adjusted_closing_value', 15, 5)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Products table
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->decimal('minimum_stock', 10, 2)->nullable()->default(0)->change();
            $table->decimal('maximum_stock', 10, 2)->nullable()->change();
        });

        // Inventory table
        Schema::table('inventory', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->default(0)->change();
            $table->decimal('reserved_quantity', 12, 4)->default(0)->change();
            $table->decimal('available_quantity', 12, 4)->default(0)->change();
            $table->decimal('unit_cost', 10, 4)->nullable()->change();
            $table->decimal('total_value', 15, 4)->default(0)->change();
            $table->decimal('last_count_quantity', 12, 4)->nullable()->change();
        });

        // Inventory movements table
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('unit_cost', 10, 4)->nullable()->change();
            $table->decimal('total_cost', 15, 4)->nullable()->change();
            $table->decimal('previous_quantity', 15, 4)->nullable()->change();
            $table->decimal('new_quantity', 15, 4)->nullable()->change();
            $table->decimal('quantity_in', 15, 4)->default(0)->change();
            $table->decimal('quantity_out', 15, 4)->default(0)->change();
            $table->decimal('balance_quantity', 15, 4)->nullable()->change();
        });

        // Purchases table
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->change();
            $table->decimal('tax_amount', 15, 2)->default(0)->change();
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
            $table->decimal('shipping_cost', 15, 2)->default(0)->change();
            $table->decimal('total', 15, 2)->default(0)->change();
        });

        // Purchase details table
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
            $table->decimal('unit_cost', 15, 2)->change();
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
            $table->decimal('tax_amount', 15, 2)->default(0)->change();
            $table->decimal('subtotal', 15, 2)->change();
            $table->decimal('total', 15, 2)->change();
        });

        // Dispatches table
        Schema::table('dispatches', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->change();
            $table->decimal('tax_amount', 15, 2)->default(0)->change();
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
            $table->decimal('shipping_cost', 15, 2)->default(0)->change();
            $table->decimal('total', 15, 2)->default(0)->change();
        });

        // Dispatch details table
        Schema::table('dispatch_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
            $table->decimal('quantity_dispatched', 15, 4)->default(0)->change();
            $table->decimal('quantity_delivered', 15, 4)->default(0)->change();
            $table->decimal('unit_price', 15, 4)->default(0)->change();
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
            $table->decimal('tax_amount', 15, 2)->default(0)->change();
            $table->decimal('subtotal', 15, 2)->default(0)->change();
            $table->decimal('total', 15, 2)->default(0)->change();
        });

        // Inventory transfers table
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->decimal('shipping_cost', 10, 2)->nullable()->change();
        });

        // Inventory transfer details table
        Schema::table('inventory_transfer_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
        });

        // Inventory adjustments table
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
            $table->decimal('unit_cost', 15, 4)->nullable()->change();
            $table->decimal('total_value', 15, 2)->default(0)->change();
        });

        // Inventory alerts table
        Schema::table('inventory_alerts', function (Blueprint $table) {
            $table->decimal('threshold_value', 12, 4)->nullable()->change();
            $table->decimal('current_value', 12, 4)->nullable()->change();
        });

        // Product lots table
        Schema::table('product_lots', function (Blueprint $table) {
            $table->decimal('quantity_produced', 15, 4)->default(0)->change();
            $table->decimal('quantity_remaining', 15, 4)->default(0)->change();
            $table->decimal('unit_cost', 15, 4)->nullable()->change();
        });

        // Donations table
        Schema::table('donations', function (Blueprint $table) {
            $table->decimal('estimated_value', 15, 2)->default(0)->change();
            $table->decimal('tax_deduction_value', 15, 2)->nullable()->change();
        });

        // Donation details table
        Schema::table('donation_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
            $table->decimal('estimated_unit_value', 15, 2)->default(0)->change();
            $table->decimal('estimated_total_value', 15, 2)->default(0)->change();
        });

        // Storage locations table
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->decimal('capacity', 12, 4)->nullable()->change();
        });

        // Product supplier table
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->decimal('supplier_cost', 12, 4)->nullable()->change();
            $table->decimal('last_purchase_price', 12, 4)->nullable()->change();
        });

        // Inventory closures table
        Schema::table('inventory_closures', function (Blueprint $table) {
            $table->decimal('total_value', 15, 2)->default(0)->change();
            $table->decimal('total_quantity', 15, 4)->default(0)->change();
            $table->decimal('total_discrepancy_value', 15, 2)->default(0)->change();
        });

        // Inventory closure details table
        Schema::table('inventory_closure_details', function (Blueprint $table) {
            $table->decimal('opening_quantity', 15, 4)->default(0)->change();
            $table->decimal('opening_unit_cost', 15, 2)->default(0)->change();
            $table->decimal('opening_total_value', 15, 2)->default(0)->change();
            $table->decimal('quantity_in', 15, 4)->default(0)->change();
            $table->decimal('quantity_out', 15, 4)->default(0)->change();
            $table->decimal('calculated_closing_quantity', 15, 4)->default(0)->change();
            $table->decimal('calculated_closing_unit_cost', 15, 2)->default(0)->change();
            $table->decimal('calculated_closing_value', 15, 2)->default(0)->change();
            $table->decimal('physical_count_quantity', 15, 4)->nullable()->change();
            $table->decimal('physical_count_unit_cost', 15, 2)->nullable()->change();
            $table->decimal('physical_count_value', 15, 2)->nullable()->change();
            $table->decimal('discrepancy_quantity', 15, 4)->default(0)->change();
            $table->decimal('discrepancy_value', 15, 2)->default(0)->change();
            $table->decimal('adjusted_closing_quantity', 15, 4)->default(0)->change();
            $table->decimal('adjusted_closing_unit_cost', 15, 2)->default(0)->change();
            $table->decimal('adjusted_closing_value', 15, 2)->default(0)->change();
        });
    }
};
