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
        Schema::create('project_shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('port_id')->nullable()->constrained('ports')->cascadeOnDelete();

            $table->string('currency')->nullable();

            $table->foreignId('staff_docs_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_declarant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_declarant_processing_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tracking_no')->nullable();
            $table->string('shipment_status')->nullable();

            $table->date('etd_min')->nullable();
            $table->date('etd_max')->nullable();
            $table->date('eta_min')->nullable();
            $table->date('eta_max')->nullable();
            $table->date('atd')->nullable();
            $table->date('ata')->nullable();

            $table->string('customs_declaration_no')->nullable();
            $table->date('customs_declaration_date')->nullable();
            $table->string('customs_clearance_status')->nullable();
            $table->date('customs_clearance_date')->nullable();

            $table->decimal('exchange_rate', 15, 3)->nullable();
            $table->boolean('is_exchange_rate_final')->default(false);

            $table->decimal('total_value', 24, 6)->nullable();
            $table->decimal('total_contract_value', 24, 6)->nullable();
            $table->json('extra_costs')->nullable();
            $table->decimal('total_extra_cost', 24, 6)->nullable();
            $table->decimal('average_cost', 15, 3)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_shipments');
    }
};
