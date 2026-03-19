<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_type_id')->constrained('socio_types');
            $table->unsignedInteger('num_socio')->unique();
            $table->string('nome');
            $table->string('morada')->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->string('localidade')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('telemovel', 30)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('numero_fiscal', 20)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->date('data_socio')->nullable();
            $table->string('estado')->default('ativo')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
