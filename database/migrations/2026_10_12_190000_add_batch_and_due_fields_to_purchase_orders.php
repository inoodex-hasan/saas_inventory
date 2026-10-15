<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 30)->default('unpaid')->after('due_amount'); // unpaid, partially_paid, paid
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('batch_number', 100)->nullable()->after('product_variant_id');
            $table->date('manufacture_date')->nullable()->after('batch_number');
            $table->date('expiry_date')->nullable()->after('manufacture_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'manufacture_date', 'expiry_date']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'due_amount', 'payment_status']);
        });
    }
};
