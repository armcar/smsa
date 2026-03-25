<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('socios')
            ->selectRaw('LOWER(TRIM(email)) as normalized_email, COUNT(*) as total')
            ->whereNotNull('email')
            ->whereRaw("TRIM(email) <> ''")
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $emails = $duplicates
                ->pluck('normalized_email')
                ->filter()
                ->take(5)
                ->implode(', ');

            throw new \RuntimeException("Existem emails duplicados em socios ({$emails}). Corrija antes de aplicar índice único.");
        }

        Schema::table('socios', function (Blueprint $table): void {
            $table->unique('email', 'socios_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table): void {
            $table->dropUnique('socios_email_unique');
        });
    }
};
