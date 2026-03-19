<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Limpar duplicados existentes: mantém o recibo com menor id e apaga os restantes
        // (mesma combinação member_id + quota_year_id)
        DB::statement("
            DELETE r1 FROM receipts r1
            INNER JOIN receipts r2
                ON r1.member_id = r2.member_id
               AND r1.quota_year_id = r2.quota_year_id
               AND r1.id > r2.id
        ");

        // 2) Adicionar constraint unique
        Schema::table('receipts', function (Blueprint $table) {
            $table->unique(['member_id', 'quota_year_id'], 'receipts_member_quota_unique');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique('receipts_member_quota_unique');
        });
    }
};
