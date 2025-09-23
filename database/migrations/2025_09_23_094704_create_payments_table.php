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

            $table->morphs('payable');

            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('supplier_contract_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('supplier_payment_id')->nullable()->constrained('contacts')->cascadeOnDelete();

            $table->string('payment_status', 50)->default(\App\Enums\PaymentStatusEnum::Pending->value);
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 24, 6)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('average_exchange_rate', 15, 3)->nullable();
            $table->text('note')->nullable();

            $table->boolean('payment_type')->nullable()->comment('0: out, 1: in');

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
