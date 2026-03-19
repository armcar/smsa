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
    Schema::create('quota_charges', function (Blueprint $table) {
        $table->id();

        $table->foreignId('socio_id')->constrained('socios')->cascadeOnDelete();
        $table->foreignId('quota_year_id')->constrained('quota_years')->cascadeOnDelete();

        // Congela o tipo no momento do lançamento (histórico)
        $table->foreignId('socio_type_id')->constrained('socio_types');

        $table->decimal('valor', 10, 2);

        // pendente / parcial / pago / anulado (texto simples)
        $table->string('estado')->default('pendente')->index();

        $table->date('emitido_em')->nullable();
        $table->date('vencimento_em')->nullable();

        $table->text('observacoes')->nullable();

        $table->timestamps();

        // 1 lançamento por sócio por ano
        $table->unique(['socio_id', 'quota_year_id']);
        $table->index(['quota_year_id', 'estado']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_charges');
    }
};
