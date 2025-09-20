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
        Schema::create('purchase_shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_shipment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('assortment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_price', 15, 3)->nullable();
            $table->decimal('contract_price', 15, 3)->nullable();
            $table->decimal('break_price', 15, 3)->nullable();

            $table->decimal('average_cost', 15, 3)->nullable();
            $table->string('currency')->nullable();
            $table->boolean('is_ready')->default(false);

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
        Schema::dropIfExists('purchase_shipment_lines');
    }
};
