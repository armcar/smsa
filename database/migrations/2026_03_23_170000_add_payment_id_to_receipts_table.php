<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('receipts', 'payment_id')) {
                $table->foreignId('payment_id')
                    ->nullable()
                    ->after('quota_year_id')
                    ->constrained('payments')
                    ->nullOnDelete();

                $table->unique('payment_id', 'receipts_payment_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'payment_id')) {
                $table->dropUnique('receipts_payment_id_unique');
                $table->dropConstrainedForeignId('payment_id');
            }
        });
    }
};
