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
    Schema::create('socios', function (Blueprint $table) {
        $table->id();

        // Nº atribuído manualmente pela Secretaria
        $table->unsignedInteger('numero_socio')->unique();

        $table->string('nome');

        $table->string('nif', 20)->nullable()->index();
        $table->string('email')->nullable()->index();
        $table->string('telemovel', 30)->nullable();

        $table->string('morada')->nullable();
        $table->string('cod_postal', 20)->nullable();
        $table->string('localidade')->nullable();

        $table->date('data_nascimento')->nullable();

        $table->foreignId('socio_type_id')->constrained('socio_types');

        // Texto simples: ativo / suspenso / desistente / falecido / etc.
        $table->string('estado')->default('ativo')->index();

        $table->date('data_entrada')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
