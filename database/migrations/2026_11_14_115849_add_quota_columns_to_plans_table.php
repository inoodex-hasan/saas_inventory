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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'max_quotations')) {
                $table->integer('max_quotations')->default(-1)->after('max_users');
            }
            if (!Schema::hasColumn('plans', 'max_sales_orders')) {
                $table->integer('max_sales_orders')->default(-1)->after('max_quotations');
            }
            if (!Schema::hasColumn('plans', 'max_purchase_orders')) {
                $table->integer('max_purchase_orders')->default(-1)->after('max_sales_orders');
            }
            if (!Schema::hasColumn('plans', 'max_projects')) {
                $table->integer('max_projects')->default(-1)->after('max_purchase_orders');
            }
            if (!Schema::hasColumn('plans', 'max_employees')) {
                $table->integer('max_employees')->default(-1)->after('max_projects');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['max_quotations', 'max_sales_orders', 'max_purchase_orders', 'max_projects', 'max_employees'] as $col) {
                if (Schema::hasColumn('plans', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
