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
        Schema::create('assortment_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assortment_id')->constrained('assortments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unique(['product_id', 'assortment_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assortment_product');
    }
};
