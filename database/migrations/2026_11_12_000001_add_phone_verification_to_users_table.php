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
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('phone_otp_code')->nullable()->after('phone_verified_at');
            $table->timestamp('phone_otp_expires_at')->nullable()->after('phone_otp_code');
            $table->unsignedTinyInteger('phone_otp_attempts')->default(0)->after('phone_otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'phone_otp_code',
                'phone_otp_expires_at',
                'phone_otp_attempts',
            ]);
        });
    }
};

