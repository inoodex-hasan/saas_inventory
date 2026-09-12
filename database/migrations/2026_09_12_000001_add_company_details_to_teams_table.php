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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('email')->nullable()->after('legal_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('tax_id')->nullable()->after('phone');
            $table->string('address_line1')->nullable()->after('tax_id');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('currency_symbol', 10)->default('$')->after('country');
            $table->string('currency_code', 10)->default('USD')->after('currency_symbol');
            $table->string('invoice_prefix', 20)->default('INV-')->after('currency_code');
            $table->string('po_prefix', 20)->default('PO-')->after('invoice_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'email',
                'phone',
                'tax_id',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'country',
                'currency_symbol',
                'currency_code',
                'invoice_prefix',
                'po_prefix',
            ]);
        });
    }
};
