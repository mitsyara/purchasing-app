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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('project_status')->nullable();
            $table->date('project_date')->nullable();
            $table->string('project_number')->unique()->nullable();
            $table->string('project_description')->nullable();
            $table->boolean('is_cif')->default(false);

            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('supplier_contract_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('supplier_payment_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('end_user_id')->nullable()->constrained('contacts')->nullOnDelete();

            $table->foreignId('import_port_id')->nullable()->constrained('ports')->nullOnDelete();

            $table->foreignId('staff_buy_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_approved_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_docs_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_declarant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_declarant_processing_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_sales_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('etd')->nullable();
            $table->date('etd_min')->nullable();
            $table->date('etd_max')->nullable();
            $table->string('eta')->nullable();
            $table->date('eta_min')->nullable();
            $table->date('eta_max')->nullable();

            $table->boolean('is_foreign')->nullable();
            $table->boolean('is_skip_invoice')->default(false);
            $table->string('incoterm')->nullable();
            $table->string('currency')->nullable();

            $table->string('payment_method')->nullable();
            $table->string('pay_term_delay_at')->nullable();
            $table->integer('pay_term_days')->default(0);

            $table->json('import_extra_costs')->nullable();
            $table->decimal('import_total_value', 24, 6)->default(0);
            $table->decimal('import_total_contract_value', 24, 6)->default(0);
            $table->decimal('import_total_extra_cost', 24, 6)->default(0);
            $table->decimal('import_total_received_value', 24, 6)->default(0);
            $table->decimal('import_total_paid_value', 24, 6)->default(0);

            $table->json('export_extra_costs')->nullable();
            $table->decimal('export_total_value', 24, 6)->default(0);
            $table->decimal('export_total_contract_value', 24, 6)->default(0);
            $table->decimal('export_total_extra_cost', 24, 6)->default(0);
            $table->decimal('export_total_received_value', 24, 6)->default(0);
            $table->decimal('export_total_paid_value', 24, 6)->default(0);

            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->longText('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('shipment_index')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
