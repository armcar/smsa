<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Garantir que não há NULLs antes de tornar obrigatório
        // Decide uma estratégia: ou corrigir manualmente, ou pôr valor placeholder.
        // Eu prefiro corrigir manualmente e bloquear a migration até estar limpo.

        $nulls = DB::table('socios')->whereNull('numero_fiscal')->count();
        if ($nulls > 0) {
            throw new RuntimeException("Existem {$nulls} sócios sem numero_fiscal. Preenche primeiro antes de tornar obrigatório.");
        }

        Schema::table('socios', function (Blueprint $table) {
            $table->string('numero_fiscal', 20)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->string('numero_fiscal', 20)->nullable()->change();
        });
    }
};