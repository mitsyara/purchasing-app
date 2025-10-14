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
        Schema::connection('mysql_customs_data')->create('customs_data_by_importers', function (Blueprint $table) {
            $table->id();
            $table->string('importer')->nullable();
            $table->index([DB::raw('importer(191)')], 'customs_data_importer_index');

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
        Schema::connection('mysql_customs_data')->dropIfExists('customs_data_by_importers');
    }
};
