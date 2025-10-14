<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_customs_data')->create('customs_data_by_importer_categories', function (Blueprint $table) {
            $table->id();

            $table->longText('importer')->nullable();
            $table->index([DB::raw('importer(191)')], 'customs_data_importer_index');

            $table->foreignId('customs_data_category_id')->nullable()->constrained('customs_data_categories')->onDelete('set null')
                ->name('fk_importer_category');

            $table->unsignedBigInteger('total_import')->default(0)->index();
            $table->decimal('total_qty', 20, 2)->default(0)->index();
            $table->decimal('total_value', 20, 2)->default(0)->index();
            $table->char('import_month', 7)->nullable()->comment('YYYY-MM')->index();
            $table->boolean('is_vett')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_customs_data')->dropIfExists('customs_data_by_importer_categories');
    }
};
