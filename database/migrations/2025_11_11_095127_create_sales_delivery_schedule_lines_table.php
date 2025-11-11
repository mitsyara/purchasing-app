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
        Schema::create('sales_delivery_schedule_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_delivery_schedule_id')->nullable()->constrained('sales_delivery_schedules')->cascadeOnDelete();
            $table->foreignId('assortment_id')->nullable()->constrained('assortments')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->decimal('qty', 15, 3)->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_schedule_lines');
    }
};
