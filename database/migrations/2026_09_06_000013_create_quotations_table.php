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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 30)->unique(); // QUO-YYYYMM-XXXX
            $table->integer('revision_number')->default(0);
            $table->foreignId('inquiry_id')->nullable()->constrained('inquiries')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Sales PIC Pembuat
            $table->foreignId('freelance_sales_id')->nullable()->constrained('freelance_sales')->onDelete('set null');
            $table->string('customer_name', 150);
            $table->string('company_name', 200);
            $table->string('delivery_terms', 100)->nullable(); // Franco Jakarta, Loco Gudang
            $table->integer('validity_days')->default(14);
            $table->date('expires_at')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('dpp', 15, 2)->default(0.00);
            $table->decimal('vat_rate_applied', 5, 2)->default(11.00); // Snapshot Statis UU HPP
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('IDR');
            $table->decimal('total_cost', 15, 2)->default(0.00); // Modal HPP Internal
            $table->decimal('gross_profit_margin', 5, 2)->default(0.00); // Persentase Margin Laba Kotor
            $table->boolean('is_approved_by_director')->default(false);
            $table->enum('status', ['draft', 'pending_director_approval', 'approved', 'sent', 'expired', 'rejected'])
                ->default('draft')
                ->index();
            $table->string('pdf_path', 255)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('director_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expires_at'], 'idx_quotations_status_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
