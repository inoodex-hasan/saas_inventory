<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clients Directory
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('company_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'name']);
            $table->index(['team_id', 'is_active']);
        });

        // 2. Cost Categories (subcontractors, permits, logistics, consumables)
        Schema::create('cost_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'name']);
        });

        // 3. Projects Master Table
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('project_code', 50)->unique();
            $table->string('name', 255);
            $table->string('status', 30)->default('planning'); // planning, in_progress, on_hold, completed, cancelled
            $table->decimal('budget', 15, 2)->default(0); // Contract / Target budget
            $table->decimal('items_total', 15, 2)->default(0); // Bill of materials billed
            $table->decimal('costs_total', 15, 2)->default(0); // Direct expenses logged
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('billed_total', 15, 2)->default(0); // Total client billing
            $table->decimal('paid_amount', 15, 2)->default(0); // Payments received
            $table->decimal('due_amount', 15, 2)->default(0); // Remaining receivables
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partially_paid, paid
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'project_code']);
            $table->index(['team_id', 'client_id']);
        });

        // 4. Project Items (Bill of Materials - Inventory issued to project)
        Schema::create('project_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('item_name', 255);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0); // Actual cost to tenant
            $table->decimal('unit_price', 15, 2)->default(0); // Billed rate to client
            $table->decimal('subtotal', 15, 2)->default(0); // quantity * unit_price
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['team_id', 'project_id']);
        });

        // 5. Project Costs (Direct non-inventory expenses)
        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('cost_category_id')->nullable()->constrained('cost_categories')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('cost_date');
            $table->string('vendor_name', 255)->nullable();
            $table->string('receipt_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'project_id']);
            $table->index(['team_id', 'cost_date']);
        });

        // 6. Project Milestone Bills & Collections
        Schema::create('project_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('bill_number', 50)->unique();
            $table->string('title', 255);
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->string('status', 30)->default('pending'); // pending, paid, overdue
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_bills');
        Schema::dropIfExists('project_costs');
        Schema::dropIfExists('project_items');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('cost_categories');
        Schema::dropIfExists('clients');
    }
};
