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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->index(['team_id', 'customer_id'], 'sales_orders_team_customer_idx');
            $table->index(['team_id', 'due_amount', 'status'], 'sales_orders_team_due_status_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['team_id', 'supplier_id'], 'purchase_orders_team_supplier_idx');
            $table->index(['team_id', 'due_amount', 'status'], 'purchase_orders_team_due_status_idx');
        });

        Schema::table('product_serials', function (Blueprint $table) {
            $table->index(['team_id', 'status', 'warranty_expires_at'], 'serials_team_status_warranty_idx');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index(['team_id', 'fiscal_year_id'], 'je_team_fiscal_year_idx');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->index(['team_id', 'chart_of_account_id', 'journal_entry_id'], 'jel_team_coa_je_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex('sales_orders_team_customer_idx');
            $table->dropIndex('sales_orders_team_due_status_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_team_supplier_idx');
            $table->dropIndex('purchase_orders_team_due_status_idx');
        });

        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropIndex('serials_team_status_warranty_idx');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('je_team_fiscal_year_idx');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropIndex('jel_team_coa_je_idx');
        });
    }
};
