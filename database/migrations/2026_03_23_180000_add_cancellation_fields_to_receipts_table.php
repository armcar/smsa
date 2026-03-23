<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            if (! Schema::hasColumn('receipts', 'anulado_em')) {
                $table->timestamp('anulado_em')->nullable()->after('data_pagamento')->index();
            }

            if (! Schema::hasColumn('receipts', 'motivo_anulacao')) {
                $table->text('motivo_anulacao')->nullable()->after('anulado_em');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            if (Schema::hasColumn('receipts', 'motivo_anulacao')) {
                $table->dropColumn('motivo_anulacao');
            }

            if (Schema::hasColumn('receipts', 'anulado_em')) {
                $table->dropColumn('anulado_em');
            }
        });
    }
};
