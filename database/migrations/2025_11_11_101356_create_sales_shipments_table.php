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
        Schema::create('sales_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_no')->nullable();
            $table->string('shipment_status')->nullable();
            $table->date('atd')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('delivery_carrier')->nullable();
            $table->string('delivery_staff')->nullable();

            $table->string('billing_address')->nullable();
            $table->string('shipping_address')->nullable();

            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_shipments');
    }
};
