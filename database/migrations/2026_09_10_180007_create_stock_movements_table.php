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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            
            // Movement type
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer_in', 'transfer_out']);
            $table->decimal('quantity', 15, 4); // positive or negative qty depending on type
            $table->decimal('unit_cost', 15, 2)->nullable();
            
            // Audit reference & reason
            $table->string('reference_type')->nullable(); // Polymorphic / custom reference
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason')->nullable();
            
            // Accountability
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast history & audit reporting
            $table->index(['team_id', 'warehouse_id', 'product_id']);
            $table->index(['team_id', 'type']);
            $table->index(['team_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
