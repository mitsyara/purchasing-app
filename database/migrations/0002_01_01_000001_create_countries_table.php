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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('alpha2', 2)->unique()->nullable();
            $table->char('alpha3', 3)->unique()->nullable();
            $table->string('country_name')->unique();
            $table->integer('phone_code')->nullable();
            $table->string('curr_code')->nullable();
            $table->string('curr_name')->nullable();
            $table->boolean('is_fav')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
