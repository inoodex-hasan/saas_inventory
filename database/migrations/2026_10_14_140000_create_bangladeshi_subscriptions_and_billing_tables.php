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
        // 1. Subscription Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_bdt', 10, 2)->default(0);
            $table->decimal('annual_price_bdt', 10, 2)->default(0);
            $table->integer('max_products')->default(100); // -1 for unlimited
            $table->integer('max_warehouses')->default(1);
            $table->integer('max_users')->default(2);
            $table->integer('max_quotations')->default(-1);
            $table->integer('max_sales_orders')->default(-1);
            $table->integer('max_purchase_orders')->default(-1);
            $table->integer('max_projects')->default(-1);
            $table->integer('max_employees')->default(-1);
            $table->json('features')->nullable();
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Tenant Subscriptions Table
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->enum('status', ['trialing', 'active', 'past_due', 'expired', 'cancelled'])->default('trialing');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancels_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });

        // 3. Subscription Payments Table
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount_bdt', 10, 2);
            $table->string('payment_method', 50); // bkash, nagad, rocket, bank_transfer, sslcommerz
            $table->string('sender_number', 50)->nullable();
            $table->string('trx_id', 100)->nullable();
            $table->string('bank_account_info', 255)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status'], 'sub_pmt_team_status_idx');
            $table->index(['trx_id'], 'sub_pmt_trx_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('plans');
    }
};
