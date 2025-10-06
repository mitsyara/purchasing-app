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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_mfg')->default(false)->index();
            $table->boolean('is_cus')->default(false)->index();
            $table->boolean('is_trader')->default(false)->index();
            $table->boolean('is_fav')->default(false)->index();

            $table->string('contact_name')->unique();
            $table->string('contact_code')->unique()->nullable();
            $table->string('contact_short_name')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->string('region')->nullable();
            $table->string('tax_code')->nullable();

            $table->string('rep_title')->nullable();
            $table->string('rep_gender')->nullable();
            $table->string('rep_name')->nullable();

            $table->string('office_address')->nullable();
            $table->string('office_email')->nullable();
            $table->string('office_phone')->nullable();

            $table->json('warehouse_addresses')->nullable();
            $table->json('bank_infos')->nullable();
            $table->json('other_infos')->nullable();

            $table->string('gmp_no')->nullable();
            $table->date('gmp_expires_at')->nullable();

            $table->longText('certificates')->nullable();
            $table->longText('notes')->nullable();

            $table->longText('contact_code_name')->storedAs('CONCAT(contact_code, " - ", contact_name)');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
