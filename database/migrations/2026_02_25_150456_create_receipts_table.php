<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            $table->string('numero')->unique();          // 2026/0001
            $table->unsignedSmallInteger('ano');         // 2026
            $table->unsignedInteger('sequencia');        // 1,2,3...

            // ATENÇÃO: no teu projeto a tabela é "socios"
            $table->foreignId('member_id')->constrained('socios')->cascadeOnDelete();

            $table->foreignId('quota_year_id')->constrained()->cascadeOnDelete();

            $table->decimal('valor', 10, 2);
            $table->date('data_pagamento');

            $table->timestamps();

            $table->unique(['ano', 'sequencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
