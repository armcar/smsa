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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('quota_charge_id')->constrained('quota_charges')->cascadeOnDelete();

        $table->date('data_pagamento');
        $table->decimal('valor', 10, 2);

        $table->string('metodo')->nullable(); // dinheiro, transferencia, mbway, multibanco...

        $table->string('documento_tipo')->nullable();   // recibo, fatura-recibo, talão...
        $table->string('documento_numero')->nullable(); // nº do documento

        $table->string('referencia')->nullable(); // ref MB / id transação / etc.
        $table->text('notas')->nullable();

        $table->timestamp('anulado_em')->nullable();

        $table->timestamps();

        $table->index(['data_pagamento']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
