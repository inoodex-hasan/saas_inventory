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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();

            $table->string('profile_name', 255);
            $table->string('subscription_number', 50);
            $table->enum('status', ['active', 'paused', 'cancelled', 'completed'])->default('active');
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'semi_annual', 'yearly'])->default('monthly');
            $table->unsignedInteger('interval_count')->default(1);

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_issue_date');
            $table->timestamp('last_issued_at')->nullable();

            $table->unsignedInteger('total_cycles_limit')->nullable(); // null = indefinite
            $table->unsignedInteger('cycles_completed')->default(0);

            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);

            $table->unsignedInteger('payment_terms_days')->default(7);
            $table->boolean('auto_send_email')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'subscription_number']);
            $table->index(['team_id', 'status', 'next_issue_date'], 'rec_inv_team_status_next_idx');
            $table->index(['team_id', 'customer_id'], 'rec_inv_team_customer_idx');
        });

        Schema::create('recurring_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_invoice_id')->constrained('recurring_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index(['recurring_invoice_id'], 'rec_items_invoice_idx');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'recurring_invoice_id')) {
                $table->foreignId('recurring_invoice_id')->nullable()->after('warehouse_id')->constrained('recurring_invoices')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'recurring_invoice_id')) {
                $table->dropForeign(['recurring_invoice_id']);
                $table->dropColumn('recurring_invoice_id');
            }
        });

        Schema::dropIfExists('recurring_invoice_items');
        Schema::dropIfExists('recurring_invoices');
    }
};
