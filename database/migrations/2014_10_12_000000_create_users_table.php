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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('document_cpf')->unique();
            $table->string('document_rg')->nullable();
            $table->string('document_rg_consignor')->nullable();
            $table->string('affiliation_date')->nullable();
            $table->string('registration_number')->nullable();
            $table->enum('nationality', ['Brasileiro', 'Estrangeiro', 'Indefinido'])->default('Indefinido');
            $table->enum('marital_status', ['Solteiro', 'Casado', 'Separado', 'Divorciado', 'Viuvo', 'Indefinido'])->default('Indefinido');
            $table->string('occupation')->nullable();
            $table->string('address')->nullable();
            $table->string('address_city_state')->nullable();
            $table->string('address_zipcode')->nullable();
            $table->string('phone_ddd')->nullable();
            $table->string('phone_number')->nullable();
            $table->enum('other_associations', ['Sim', 'Nao', 'Indefinido'])->default('Indefinido');
            $table->enum('payment_type', ['OutrosBancosParaAnipp', 'BBParaBBAnipp', 'Indefinido'])->default('Indefinido');
            $table->string('code_bank')->nullable();
            $table->string('agency_bank')->nullable();
            $table->string('account_bank')->nullable();
            $table->enum('financial_situation', ['Adimplente', 'Inadimplente', 'Indefinido'])->default('Indefinido');
            $table->string('date_of_birth')->nullable();
            $table->boolean('isActive')->default(true);
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
