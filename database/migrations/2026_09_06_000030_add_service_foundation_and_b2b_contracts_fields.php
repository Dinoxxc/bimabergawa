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
        Schema::table('categories', function (Blueprint $table) {
            $table->json('spec_template')->nullable()->after('description');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('exchange_rate_applied', 12, 4)->default(1.0000)->after('currency');
            $table->string('base_currency', 3)->default('IDR')->after('exchange_rate_applied');
            $table->string('idempotency_key', 64)->nullable()->unique()->after('director_notes');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('exchange_rate_applied', 12, 4)->default(1.0000)->after('unit_price');
            $table->string('base_currency', 3)->default('IDR')->after('exchange_rate_applied');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('approved_at');
        });

        Schema::table('commission_payouts', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('paid_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'model_number', 'description'], 'idx_products_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText('idx_products_fulltext');
        });

        Schema::table('commission_payouts', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate_applied', 'base_currency']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['exchange_rate_applied', 'base_currency', 'idempotency_key']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['spec_template']);
        });
    }
};
