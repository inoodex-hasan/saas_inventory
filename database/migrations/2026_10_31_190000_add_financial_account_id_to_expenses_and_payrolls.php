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
        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'financial_account_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->foreignId('financial_account_id')->nullable()->after('employee_id')->constrained('financial_accounts')->nullOnDelete();
            });
        }

        if (Schema::hasTable('payrolls') && !Schema::hasColumn('payrolls', 'financial_account_id')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->foreignId('financial_account_id')->nullable()->after('payment_method')->constrained('financial_accounts')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'financial_account_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('financial_account_id');
            });
        }

        if (Schema::hasTable('payrolls') && Schema::hasColumn('payrolls', 'financial_account_id')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->dropConstrainedForeignId('financial_account_id');
            });
        }
    }
};

