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
        Schema::create('project_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_shipment_id')->nullable()->constrained('project_shipments')->cascadeOnDelete();
            $table->foreignId('project_item_id')->nullable()->constrained('project_items')->cascadeOnDelete();

            $table->foreignId('assortment_id')->nullable()->constrained('assortments')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_price', 15, 3)->nullable();
            $table->decimal('contract_price', 15, 3)->nullable();

            $table->decimal('average_cost', 15, 3)->nullable();
            $table->string('currency')->nullable();
            $table->decimal('exchange_rate', 15, 6)->nullable();

            $table->decimal('display_contract_price', 15, 3)->storedAs('COALESCE(contract_price, unit_price)');
            $table->decimal('value', 24, 6)->storedAs('qty * unit_price');
            $table->decimal('contract_value', 24, 6)->storedAs('qty * display_contract_price');
            $table->decimal('total_cost', 24, 6)->storedAs('qty * average_cost');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_shipment_items');
    }
};
