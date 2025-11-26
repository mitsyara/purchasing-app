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
        Schema::create('inventory_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->nullable()->constrained('inventory_transfers')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->decimal('transfer_qty', 15, 3);
            $table->decimal('extra_cost', 15, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_lines');
    }
};
