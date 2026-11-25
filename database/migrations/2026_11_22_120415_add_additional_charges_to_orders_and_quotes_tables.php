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
            $table->decimal('additional_charges_total', 15, 2)->default(0.00)->after('shipping_cost');
            $table->json('additional_charges')->nullable()->after('additional_charges_total');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('additional_charges_total', 15, 2)->default(0.00)->after('shipping_cost');
            $table->json('additional_charges')->nullable()->after('additional_charges_total');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('additional_charges_total', 15, 2)->default(0.00)->after('tax_amount');
            $table->json('additional_charges')->nullable()->after('additional_charges_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['additional_charges_total', 'additional_charges']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['additional_charges_total', 'additional_charges']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['additional_charges_total', 'additional_charges']);
        });
    }
};
