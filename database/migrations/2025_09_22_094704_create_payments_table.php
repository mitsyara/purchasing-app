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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_shipment_id')->nullable()->constrained('purchase_shipments')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('supplier_contract_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('supplier_payment_id')->nullable()->constrained('contacts')->cascadeOnDelete();

            $table->string('payment_method', 50);
            $table->date('payment_date');
            $table->string('status', 50);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10);
            $table->decimal('exchange_rate', 15, 4)->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
