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
        Schema::create('freelance_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_tier_id')
                ->nullable()
                ->constrained('commission_tiers')
                ->onDelete('set null');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('name', 150);
            $table->string('referral_code', 50)->unique();
            $table->text('nik'); // Encrypted At-Rest (UU PDP No. 27/2022)
            $table->text('npwp_number')->nullable(); // Encrypted At-Rest
            $table->string('ktp_file_path', 255)->nullable();
            $table->string('bank_name', 100);
            $table->text('bank_account_number'); // Encrypted At-Rest
            $table->string('bank_account_name', 150);
            $table->string('industry_focus', 200)->nullable();
            $table->enum('status', ['pending_review', 'approved', 'revision_required', 'rejected', 'suspended'])
                ->default('pending_review')
                ->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_sales');
    }
};
