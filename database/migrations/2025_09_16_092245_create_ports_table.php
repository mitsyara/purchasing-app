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
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('port_code')->unique()->nullable();
            $table->string('port_name')->unique();
            $table->string('port_address')->nullable();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('region')->nullable();
            $table->string('port_type')->nullable();
            $table->json('phones')->nullable();
            $table->json('emails')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ports');
    }
};
