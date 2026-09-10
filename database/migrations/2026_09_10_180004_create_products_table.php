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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->default('pcs'); // pcs, kg, box, etc.
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->integer('reorder_level')->default(0);
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'sku']);
            $table->index(['team_id', 'barcode']);
            $table->index(['team_id', 'is_active']);
            $table->index(['team_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
