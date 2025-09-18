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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique()->nullable();
            $table->string('product_name');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_fav')->default(false);
            
            $table->foreignId('mfg_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->foreignId('packing_id')->nullable()->constrained('packings')->cascadeOnDelete();

            $table->unsignedBigInteger('product_alert_qty')->nullable();
            $table->unsignedBigInteger('product_life_cycle')->default(0);
            $table->longText('product_certificates')->nullable();
            $table->longText('product_notes')->nullable();

            $table->longText('product_full_name')->nullable();
            $table->string('product_unit_label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
