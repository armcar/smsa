<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $foreignKeys = DB::select("
            SELECT DISTINCT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payments'
              AND COLUMN_NAME = 'quota_charge_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $fk) {
            $fkName = $fk->CONSTRAINT_NAME ?? null;
            if ($fkName) {
                DB::statement("ALTER TABLE `payments` DROP FOREIGN KEY `{$fkName}`");
            }
        }

        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payments'
              AND COLUMN_NAME = 'quota_charge_id'
              AND NON_UNIQUE = 0
        ");

        foreach ($indexes as $index) {
            $name = $index->INDEX_NAME ?? null;
            if ($name) {
                DB::statement("ALTER TABLE `payments` DROP INDEX `{$name}`");
            }
        }

        DB::statement("ALTER TABLE `payments` ADD INDEX `payments_quota_charge_id_index` (`quota_charge_id`)");
        DB::statement("
            ALTER TABLE `payments`
            ADD CONSTRAINT `payments_quota_charge_id_foreign`
            FOREIGN KEY (`quota_charge_id`) REFERENCES `quota_charges`(`id`)
            ON DELETE CASCADE
        ");
    }

    public function down(): void
    {
        // Intencionalmente sem recriar UNIQUE: o modelo atual permite histórico de pagamentos.
    }
};
