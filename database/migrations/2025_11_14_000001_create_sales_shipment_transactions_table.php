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
        Schema::create('sales_shipment_transactions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('inventory_transaction_id')
                ->constrained('inventory_transactions')
                ->cascadeOnDelete()
                ->name('sst_inventory_transaction_fk');
                
            $table->foreignId('sales_delivery_schedule_line_id')
                ->constrained('sales_delivery_schedule_lines')
                ->cascadeOnDelete()
                ->name('sst_schedule_line_fk');

            $table->decimal('qty', 15, 3);
            
            $table->timestamps();
            
            // Index và unique constraint
            $table->unique(['inventory_transaction_id', 'sales_delivery_schedule_line_id'], 'sst_unique');
            $table->index('inventory_transaction_id', 'sst_inventory_idx');
            $table->index('sales_delivery_schedule_line_id', 'sst_schedule_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_shipment_transactions');
    }
};