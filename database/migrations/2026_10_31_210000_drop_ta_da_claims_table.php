<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ta_da_claims');
    }

    public function down(): void
    {
        Schema::create('ta_da_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('claim_number', 50)->unique();
            $table->date('travel_date');
            $table->string('destination', 255);
            $table->text('purpose');
            $table->decimal('travel_allowance', 14, 2)->default(0);
            $table->decimal('daily_allowance', 14, 2)->default(0);
            $table->decimal('other_expenses', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->string('receipt_reference', 100)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }
};

