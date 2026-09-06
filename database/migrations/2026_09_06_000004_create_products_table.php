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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->onDelete('restrict');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('successor_product_id')
                ->nullable()
                ->constrained('products')
                ->onDelete('set null');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('model_number', 150)->index();
            $table->decimal('cost_price', 15, 2)->default(0.00); // HPP Internal (Bebas bocor ke publik)
            $table->longText('description')->nullable();
            $table->string('datasheet_path', 255)->nullable();
            $table->enum('availability_status', ['ready_stock', 'indent_principal', 'discontinued'])
                ->default('ready_stock')
                ->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->string('meta_description', 255)->nullable();
            $table->timestamps();

            // Composite index untuk faceted live filter di katalog publik
            $table->index(['is_published', 'brand_id', 'category_id'], 'idx_products_catalog_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
