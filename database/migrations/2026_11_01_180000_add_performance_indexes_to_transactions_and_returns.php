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
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->index(['team_id', 'financial_account_id', 'transaction_date'], 'ft_team_account_date_idx');
        });

        Schema::table('product_return_items', function (Blueprint $table) {
            $table->index(['team_id', 'sales_order_item_id'], 'pri_team_so_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropIndex('ft_team_account_date_idx');
        });

        Schema::table('product_return_items', function (Blueprint $table) {
            $table->dropIndex('pri_team_so_item_idx');
        });
    }
};

