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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_code')->unique();
            $table->string('category_name');
            $table->foreignId('vat_id')->nullable()->constrained('vats')->nullOnDelete();
            $table->boolean('is_gmp_required')->default(false);
            $table->longText('category_keywords')->nullable();
            $table->longText('notes')->nullable();
            $table->unsignedBigInteger('category_index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
