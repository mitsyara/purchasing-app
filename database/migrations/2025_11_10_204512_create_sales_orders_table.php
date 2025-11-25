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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('customer_contract_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('customer_payment_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('export_warehouse_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('export_port_id')->nullable()->constrained('ports')->cascadeOnDelete();
            $table->foreignId('staff_sales_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('staff_approved_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->string('order_status')->nullable();
            $table->date('order_date')->nullable();
            $table->string('order_number')->nullable();
            $table->string('order_description')->nullable();

            $table->date('etd_min')->nullable();
            $table->date('etd_max')->nullable();
            $table->date('eta_min')->nullable();
            $table->date('eta_max')->nullable();

            $table->boolean('is_skip_invoice')->nullable();
            $table->string('currency', 3)->nullable();

            $table->string('pay_term_delay_at')->nullable();
            $table->integer('pay_term_days')->nullable();
            $table->string('payment_method')->nullable();

            $table->decimal('total_value', 24, 6)->nullable();
            $table->decimal('total_contract_value', 24, 6)->nullable();

            $table->json('extra_costs')->nullable();
            $table->decimal('total_extra_cost', 24, 6)->nullable();

            $table->decimal('total_received_value', 24, 6)->nullable();
            $table->decimal('total_paid_value', 24, 6)->nullable();

            $table->longText('notes')->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->unsignedInteger('shipment_index')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
