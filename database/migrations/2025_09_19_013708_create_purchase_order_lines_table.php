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
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('assortment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();

            $table->decimal('qty', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->string('currency')->nullable();
            $table->decimal('contract_price', 15, 2)->nullable();
            $table->decimal('extra_cost', 15, 2)->default(0);

            $table->decimal('display_contract_price', 15, 2)->storedAs('COALESCE(contract_price, unit_price)');
            $table->decimal('value', 24, 2)->storedAs('qty * unit_price');
            $table->decimal('contract_value', 24, 2)->storedAs('qty * display_contract_price');

            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
