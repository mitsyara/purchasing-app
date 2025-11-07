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
        // Thêm assortment_id vào purchase_order_lines
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('assortment_id')->nullable()->after('product_id');
            $table->foreign('assortment_id')->references('id')->on('assortments')->onDelete('set null');
            $table->index('assortment_id');
        });

        // Thêm assortment_id vào project_items (nếu table tồn tại)
        if (Schema::hasTable('project_items')) {
            Schema::table('project_items', function (Blueprint $table) {
                $table->unsignedBigInteger('assortment_id')->nullable()->after('product_id');
                $table->foreign('assortment_id')->references('id')->on('assortments')->onDelete('set null');
                $table->index('assortment_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from purchase_order_lines
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['assortment_id']);
            $table->dropIndex(['assortment_id']);
            $table->dropColumn('assortment_id');
        });

        // Remove from project_items (nếu table tồn tại)
        if (Schema::hasTable('project_items')) {
            Schema::table('project_items', function (Blueprint $table) {
                $table->dropForeign(['assortment_id']);
                $table->dropIndex(['assortment_id']);
                $table->dropColumn('assortment_id');
            });
        }
    }
};
