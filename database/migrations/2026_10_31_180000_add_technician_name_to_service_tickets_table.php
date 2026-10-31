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
        Schema::table('service_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('service_tickets', 'technician_name')) {
                $table->string('technician_name', 100)->nullable()->after('technician_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('service_tickets', 'technician_name')) {
                $table->dropColumn('technician_name');
            }
        });
    }
};

