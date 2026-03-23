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

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $foreignKeys = DB::select("\n            SELECT DISTINCT CONSTRAINT_NAME\n            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE\n            WHERE TABLE_SCHEMA = DATABASE()\n              AND TABLE_NAME = 'payments'\n              AND COLUMN_NAME = 'quota_charge_id'\n              AND REFERENCED_TABLE_NAME IS NOT NULL\n        ");

        foreach ($foreignKeys as $fk) {
            $fkName = $fk->CONSTRAINT_NAME ?? null;
            if ($fkName) {
                DB::statement("ALTER TABLE `payments` DROP FOREIGN KEY `{$fkName}`");
            }
        }

        $indexes = DB::select("\n            SELECT DISTINCT INDEX_NAME\n            FROM INFORMATION_SCHEMA.STATISTICS\n            WHERE TABLE_SCHEMA = DATABASE()\n              AND TABLE_NAME = 'payments'\n              AND COLUMN_NAME = 'quota_charge_id'\n              AND NON_UNIQUE = 0\n        ");

        foreach ($indexes as $index) {
            $name = $index->INDEX_NAME ?? null;
            if ($name) {
                DB::statement("ALTER TABLE `payments` DROP INDEX `{$name}`");
            }
        }

        DB::statement("ALTER TABLE `payments` ADD INDEX `payments_quota_charge_id_index` (`quota_charge_id`)");
        DB::statement("\n            ALTER TABLE `payments`\n            ADD CONSTRAINT `payments_quota_charge_id_foreign`\n            FOREIGN KEY (`quota_charge_id`) REFERENCES `quota_charges`(`id`)\n            ON DELETE CASCADE\n        ");
    }

    public function down(): void
    {
        // Intencionalmente sem recriar UNIQUE: o modelo atual permite historico de pagamentos.
    }
};