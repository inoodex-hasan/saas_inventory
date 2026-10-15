<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number', 50)->unique();
            $table->string('device_name', 255);
            $table->string('device_brand', 100)->nullable();
            $table->string('serial_no', 100)->nullable();
            $table->string('accessories_received', 255)->nullable();
            $table->text('problem_description')->nullable();
            $table->text('initial_diagnosis')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('received'); // received, diagnosing, waiting_parts, repaired, delivered, cancelled
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('parts_total', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partially_paid, paid
            $table->integer('warranty_days')->default(0);
            $table->date('received_date');
            $table->date('estimated_completion_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'ticket_number']);
            $table->index(['team_id', 'customer_id']);
            $table->index(['team_id', 'received_date']);
        });

        Schema::create('service_ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('part_name', 255);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['team_id', 'service_ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_items');
        Schema::dropIfExists('service_tickets');
    }
};
