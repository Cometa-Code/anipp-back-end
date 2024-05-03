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
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->nullable()->cascadeOnDelete();
            $table->enum('type', ['Entrada', 'Saida']);
            $table->string('date');
            $table->string('origin_agency')->nullable();
            $table->string('allotment')->nullable();
            $table->string('document_number')->nullable();
            $table->string('history_code')->nullable();
            $table->string('history')->nullable();
            $table->decimal('value', 10, 2);
            $table->string('history_detail')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
