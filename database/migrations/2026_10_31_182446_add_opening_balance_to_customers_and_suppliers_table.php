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
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'opening_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('opening_balance', 15, 2)->default(0.00)->after('address');
            });
        }

        if (Schema::hasTable('suppliers') && !Schema::hasColumn('suppliers', 'opening_balance')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->decimal('opening_balance', 15, 2)->default(0.00)->after('address');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'opening_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('opening_balance');
            });
        }

        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'opening_balance')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('opening_balance');
            });
        }
    }
};
