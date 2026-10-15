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
        // 1. Expense Categories Table
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'is_active']);
        });

        // 2. Employees Directory Table
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 50);
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('designation');
            $table->string('department')->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('mobile_banking_no')->nullable();
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['team_id', 'employee_code']);
            $table->index(['team_id', 'status']);
        });

        // 3. General Daily Expenses Table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('expense_number', 50);
            $table->string('title');
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->string('payment_method', 50)->default('cash');
            $table->string('vendor_or_payee')->nullable();
            $table->string('receipt_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'expense_number']);
            $table->index(['team_id', 'expense_date']);
        });

        // 4. TA / DA Claims (Travel Allowance / Daily Allowance) Table
        Schema::create('ta_da_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('claim_number', 50);
            $table->date('travel_date');
            $table->string('destination');
            $table->string('purpose');
            $table->decimal('travel_allowance', 14, 2)->default(0);
            $table->decimal('daily_allowance', 14, 2)->default(0);
            $table->decimal('other_expenses', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'reimbursed'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'claim_number']);
            $table->index(['team_id', 'status']);
        });

        // 5. Monthly Payroll & Salaries Table
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('payroll_number', 50);
            $table->string('month_year', 20); // e.g. "2026-10"
            $table->decimal('basic_salary', 14, 2);
            $table->decimal('tada_allowance', 14, 2)->default(0);
            $table->decimal('other_allowances', 14, 2)->default(0);
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2);
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'payroll_number']);
            $table->unique(['team_id', 'employee_id', 'month_year']);
            $table->index(['team_id', 'month_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('ta_da_claims');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('expense_categories');
    }
};
