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
        Schema::create('assortments', function (Blueprint $table) {
            $table->id();
            $table->string('assortment_code')->unique();
            $table->string('assortment_name')->unique();
            $table->longText('assortment_description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->longText('assortment_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assortments');
    }
};
