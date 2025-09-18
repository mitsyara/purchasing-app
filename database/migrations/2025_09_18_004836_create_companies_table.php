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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_code')->unique();
            $table->string('company_name');
            $table->string('company_tax_id')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->longText('company_address')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_owner_gender')->nullable();
            $table->string('company_owner')->nullable();
            $table->string('company_owner_title')->nullable();
            $table->string('company_website')->nullable();
            $table->longText('company_logo')->nullable();
            $table->string('company_color')->nullable();
            $table->json('company_bank_accounts')->nullable();
            $table->string('company_currency')->nullable();
            $table->string('company_language')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
