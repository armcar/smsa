<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Limpar duplicados existentes: mantem o recibo com menor id e apaga os restantes.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("\n                DELETE FROM receipts\n                WHERE id NOT IN (\n                    SELECT MIN(id)\n                    FROM receipts\n                    GROUP BY member_id, quota_year_id\n                )\n            ");
        } else {
            DB::statement("\n                DELETE r1 FROM receipts r1\n                INNER JOIN receipts r2\n                    ON r1.member_id = r2.member_id\n                   AND r1.quota_year_id = r2.quota_year_id\n                   AND r1.id > r2.id\n            ");
        }

        Schema::table('receipts', function (Blueprint $table): void {
            $table->unique(['member_id', 'quota_year_id'], 'receipts_member_quota_unique');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->dropUnique('receipts_member_quota_unique');
        });
    }
};