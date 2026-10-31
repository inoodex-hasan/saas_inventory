<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->string('payment_method', 100)->nullable()->after('payment_status');
            $table->foreignId('financial_account_id')->nullable()->after('payment_method')->constrained('financial_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropForeign(['financial_account_id']);
            $table->dropColumn(['financial_account_id', 'payment_method']);
        });
    }
};

