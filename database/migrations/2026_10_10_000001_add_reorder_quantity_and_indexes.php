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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'reorder_quantity')) {
                $table->integer('reorder_quantity')->default(0)->after('reorder_level');
            }
            $table->index(['team_id', 'name'], 'products_team_id_name_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('reason', 'stock_movements_reason_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_team_id_name_index');
            if (Schema::hasColumn('products', 'reorder_quantity')) {
                $table->dropColumn('reorder_quantity');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_reason_index');
        });
    }
};
