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
        Schema::create('order_charge_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('applies_to', ['sales', 'purchases', 'both'])->default('sales');
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('default_amount', 12, 2)->default(0.00);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'applies_to', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_charge_fields');
    }
};
