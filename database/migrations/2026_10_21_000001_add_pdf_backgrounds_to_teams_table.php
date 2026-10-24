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
            $table->string('invoice_bg_path')->nullable()->after('po_prefix');
            $table->string('report_bg_path')->nullable()->after('invoice_bg_path');
            $table->unsignedSmallInteger('invoice_margin_top')->default(48)->after('report_bg_path');
            $table->unsignedSmallInteger('invoice_margin_bottom')->default(25)->after('invoice_margin_top');
            $table->unsignedSmallInteger('report_margin_top')->default(42)->after('invoice_margin_bottom');
            $table->unsignedSmallInteger('report_margin_bottom')->default(25)->after('report_margin_top');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_bg_path',
                'report_bg_path',
                'invoice_margin_top',
                'invoice_margin_bottom',
                'report_margin_top',
                'report_margin_bottom',
            ]);
        });
    }
};
