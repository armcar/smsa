<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receipts')) {
            return;
        }

        if (! $this->hasIndex('receipts', 'receipts_member_id_index')) {
            Schema::table('receipts', function (Blueprint $table): void {
                $table->index('member_id', 'receipts_member_id_index');
            });
        }

        if (! $this->hasIndex('receipts', 'receipts_quota_year_id_index')) {
            Schema::table('receipts', function (Blueprint $table): void {
                $table->index('quota_year_id', 'receipts_quota_year_id_index');
            });
        }

        if ($this->hasIndex('receipts', 'receipts_member_quota_unique')) {
            Schema::table('receipts', function (Blueprint $table): void {
                $table->dropUnique('receipts_member_quota_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('receipts')) {
            return;
        }

        if (! $this->hasIndex('receipts', 'receipts_member_quota_unique')) {
            Schema::table('receipts', function (Blueprint $table): void {
                $table->unique(['member_id', 'quota_year_id'], 'receipts_member_quota_unique');
            });
        }

        if ($this->hasIndex('receipts', 'receipts_quota_year_id_index')) {
            Schema::table('receipts', function (Blueprint $table): void {
                $table->dropIndex('receipts_quota_year_id_index');
            });
        }

        if ($this->hasIndex('receipts', 'receipts_member_id_index')) {
            Schema::table('receipts', function (Blueprint $table): void {
                $table->dropIndex('receipts_member_id_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list('{$table}')"));

            return $indexes->contains(function ($idx) use ($indexName): bool {
                return (string) ($idx->name ?? '') === $indexName;
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM ' . $table));

        return $indexes->contains(function ($idx) use ($indexName): bool {
            return (string) ($idx->Key_name ?? '') === $indexName;
        });
    }
};