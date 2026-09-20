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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name')->index('idx_brands_code');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name')->index('idx_categories_code');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 50)->nullable()->after('model_number')->unique('idx_products_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('idx_products_sku_unique');
            $table->dropColumn('sku');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_code');
            $table->dropColumn('code');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('idx_brands_code');
            $table->dropColumn('code');
        });
    }
};
