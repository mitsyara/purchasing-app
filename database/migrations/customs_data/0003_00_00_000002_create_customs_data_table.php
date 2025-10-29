<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_customs_data';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_customs_data')->create('customs_data', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('category_id')->nullable()->constrained('customs_data_categories')->nullOnDelete();
            $table->foreignId('customs_data_category_id')->nullable()->constrained('customs_data_categories')->nullOnDelete();
            $table->date('import_date')->nullable()->index();
            $table->longText('importer')->nullable();
            $table->longText('product');
            $table->string('unit')->nullable();
            $table->decimal('qty', 24, 3)->nullable();
            $table->decimal('price', 24, 3)->nullable();
            $table->decimal('value', 24, 6)->storedAs('COALESCE(qty, 0) * COALESCE(price, 0)');
            $table->string('export_country')->nullable();
            $table->longText('exporter')->nullable();
            $table->string('incoterm')->nullable();
            $table->string('hscode')->nullable();
            $table->string('category_keywords_hash', 32)->nullable()->index();

            $table->index([DB::raw('importer(191)')], 'customs_data_importer_index');
            $table->index([DB::raw('product(191)')], 'customs_data_product_index');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_customs_data')->dropIfExists('customs_data');
    }
};
