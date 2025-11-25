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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('inventory_transactions')->cascadeOnDelete();

            $table->morphs('sourceable');
            $table->string('transaction_direction', 10)->nullable(); // e.g., 'in', 'out'
            $table->date('transaction_date')->nullable();
            $table->decimal('qty', 15, 3);
            $table->string('lot_no')->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();

            $table->decimal('break_price', 15, 4)->nullable();
            $table->decimal('io_price', 15, 4)->nullable();
            $table->string('io_currency', 4)->nullable();

            $table->boolean('is_checked')->default(false);
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
