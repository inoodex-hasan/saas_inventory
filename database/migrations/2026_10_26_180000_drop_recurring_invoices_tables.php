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
            if (Schema::hasColumn('sales_orders', 'recurring_invoice_id')) {
                // Drop foreign key if exists
                try {
                    $table->dropForeign(['recurring_invoice_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key did not exist or was named differently
                }
                $table->dropColumn('recurring_invoice_id');
            }
        });

        Schema::dropIfExists('recurring_invoice_items');
        Schema::dropIfExists('recurring_invoices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropped tables - no rollback needed
    }
};
