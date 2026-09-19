<?php

declare(strict_types=1);

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
        Schema::table('products', function (Blueprint $table) {
            $table->string('uom', 20)->default('pcs')->after('currency');
            $table->integer('moq')->default(1)->after('uom');
            $table->string('warranty_period', 50)->nullable()->after('moq');
        });

        Schema::table('product_specifications', function (Blueprint $table) {
            $table->string('spec_group', 100)->nullable()->after('product_id');
            $table->string('spec_unit', 50)->nullable()->after('spec_value');
            $table->boolean('is_highlight')->default(false)->after('spec_unit');

            $table->index(['product_id', 'is_highlight'], 'idx_prod_specs_highlight');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('project_name', 200)->nullable()->after('company_name');
        });

        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->string('uom', 20)->default('pcs')->after('quantity');
            $table->json('custom_specifications')->nullable()->after('uom');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('project_name', 200)->nullable()->after('company_name');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('uom', 20)->default('pcs')->after('quantity');
            $table->string('warranty_period', 50)->nullable()->after('uom');
            $table->json('custom_specifications')->nullable()->after('warranty_period');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('project_name', 200)->nullable()->after('customer_po_file_path');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->string('driver_name', 100)->nullable()->after('courier_name');
            $table->string('vehicle_plate_number', 20)->nullable()->after('driver_name');
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->string('uom', 20)->default('pcs')->after('quantity_shipped');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('company_npwp', 30)->nullable()->after('company_name');
            $table->string('faktur_pajak_number', 35)->nullable()->after('invoice_number');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('uom', 20)->default('pcs')->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['uom']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['company_npwp', 'faktur_pajak_number']);
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn(['uom']);
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'vehicle_plate_number']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['project_name']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['uom', 'warranty_period', 'custom_specifications']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['project_name']);
        });

        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->dropColumn(['uom', 'custom_specifications']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['project_name']);
        });

        Schema::table('product_specifications', function (Blueprint $table) {
            $table->dropIndex('idx_prod_specs_highlight');
            $table->dropColumn(['spec_group', 'spec_unit', 'is_highlight']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['uom', 'moq', 'warranty_period']);
        });
    }
};
