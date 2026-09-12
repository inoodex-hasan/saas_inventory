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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('email');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('personal_team'); // active, suspended, trial
            $table->string('subscription_plan', 50)->default('pro')->after('status');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['status', 'subscription_plan', 'subscription_expires_at']);
        });
    }
};
