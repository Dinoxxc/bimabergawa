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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_number', 30)->unique(); // INQ-YYYYMM-XXXX
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->foreignId('freelance_sales_id')
                ->nullable()
                ->constrained('freelance_sales')
                ->onDelete('set null');
            $table->string('customer_name', 150);
            $table->string('company_name', 200);
            $table->string('phone_wa', 50)->index();
            $table->string('email', 150)->nullable();
            $table->string('project_location', 200)->nullable();
            $table->enum('type', ['single_direct', 'bulk_cart', 'manual_offline'])->default('single_direct');
            $table->enum('source', ['website_rfq', 'direct_whatsapp', 'walk_in_phone', 'exhibition', 'referral'])->default('website_rfq');
            $table->enum('status', ['new', 'contacted', 'quote_draft', 'quote_sent', 'deal', 'lost'])->default('new')->index();
            $table->text('notes')->nullable();
            $table->string('boq_attachment_path', 255)->nullable();
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at'], 'idx_inquiries_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
