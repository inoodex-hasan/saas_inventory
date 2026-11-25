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
            if (!Schema::hasColumn('teams', 'invoice_template')) {
                $table->string('invoice_template', 50)->default('invoice_pdf1')->after('po_prefix');
            }
            if (!Schema::hasColumn('teams', 'hide_header_on_pad')) {
                $table->boolean('hide_header_on_pad')->default(false)->after('invoice_bg_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['invoice_template', 'hide_header_on_pad']);
        });
    }
};
