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
        Schema::create('customs_data_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('keywords')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('current_index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customs_data_categories');
    }
};
