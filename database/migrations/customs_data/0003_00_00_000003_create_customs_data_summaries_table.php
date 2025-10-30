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
        Schema::connection('mysql_customs_data')->create('customs_data_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customs_data_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('importer');
            $table->date('import_date');

            $table->unsignedBigInteger('total_import')->default(0);
            $table->decimal('total_qty', 24, 4)->default(0);
            $table->decimal('total_value', 24, 4)->default(0);
            $table->boolean('is_vett')->default(0)->index();

            $table->timestamps();

            // ✅ Required indexes
            $table->unique(['customs_data_category_id', 'importer', 'import_date'], 'uniq_cat_company_date');
            
            // ✅ Performance indexes for UI queries (full optimization)
            $table->index(['import_date', 'customs_data_category_id'], 'idx_date_category');
            $table->index(['is_vett', 'customs_data_category_id', 'import_date'], 'idx_filters_main');
            
            // ✅ FULLTEXT index cho LIKE %search% patterns
            $table->fullText(['importer'], 'ft_importer_search');
            
            // ✅ Regular prefix index cho LIKE 'prefix%' patterns  
            $table->index([DB::raw('importer(30)')], 'idx_importer_prefix');
            
            // ✅ Composite indexes for GROUP BY optimization
            $table->index(['importer', 'customs_data_category_id'], 'idx_group_by_main');
            $table->index(['is_vett', 'importer'], 'idx_vett_importer');
            
            // ✅ Additional indexes for common filter combinations
            $table->index(['import_date', 'is_vett', 'customs_data_category_id'], 'idx_date_vett_category');
            $table->index(['customs_data_category_id', 'import_date', 'is_vett'], 'idx_category_date_vett');
            
            // ✅ Index for sorting optimization
            $table->index(['total_value', 'importer'], 'idx_value_sort');
            $table->index(['total_import', 'importer'], 'idx_import_sort');
            
            // ✅ Covering index for summary queries (includes all needed columns)
            $table->index([
                'importer', 
                'customs_data_category_id', 
                'import_date', 
                'is_vett',
                'total_value'
            ], 'idx_covering_summary');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_customs_data')->dropIfExists('customs_data_summaries');
    }
};
