<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $hasUnique = false;

        if (DB::getDriverName() === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list('payments')"));
            $hasUnique = $indexes->contains(fn ($idx): bool => (string) ($idx->name ?? '') === 'payments_quota_charge_id_unique');
        } else {
            $hasUnique = collect(DB::select('SHOW INDEX FROM payments WHERE Key_name = ?', [
                'payments_quota_charge_id_unique',
            ]))->isNotEmpty();
        }

        if ($hasUnique) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropUnique('payments_quota_charge_id_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->unique('quota_charge_id');
        });
    }
};