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
        Schema::create('sales_delivery_schedule_shipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_shipment_id')->nullable()
                ->constrained('sales_shipments')->cascadeOnDelete()
                ->name('sdss_shipment_id_fk');
            $table->foreignId('sales_delivery_schedule_id')->nullable()
                ->constrained('sales_delivery_schedules')->cascadeOnDelete()
                ->name('sdss_schedule_id_fk');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_schedule_shipment');
    }
};
