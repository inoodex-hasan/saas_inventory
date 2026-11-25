<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the 3 default templates
        DB::table('invoice_templates')->insert([
            [
                'key' => 'invoice_pdf1',
                'name' => 'Classic Modern',
                'description' => 'Dark slate header, rounded customer info card, line items with warranty/SKU, in-words summary, and dual signatures.',
                'thumbnail' => null,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'invoice_pdf2',
                'name' => 'Minimal Executive',
                'description' => 'Bold uppercase typography, 2-column Bill To & Ship To cards, clean items table, formal stamp & dual signature boxes, and pinned footer.',
                'thumbnail' => null,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'invoice_pdf3',
                'name' => 'Corporate Navy & Gold',
                'description' => 'Premium navy (#0a1c38) & gold (#b8860b) accents, structured two-box info section, compact key-value data, and returns support.',
                'thumbnail' => null,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
    }
};
