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
        Schema::connection('mysql_customs_data')->create('customs_data_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('keywords')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('current_index')->default(0);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_customs_data')->dropIfExists('customs_data_categories');
    }
};
