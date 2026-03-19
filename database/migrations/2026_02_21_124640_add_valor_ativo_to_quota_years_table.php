<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quota_years', function (Blueprint $table) {
            if (! Schema::hasColumn('quota_years', 'valor')) {
                $table->decimal('valor', 8, 2)->default(0)->after('ano');
            }

            if (! Schema::hasColumn('quota_years', 'ativo')) {
                $table->boolean('ativo')->default(false)->after('valor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quota_years', function (Blueprint $table) {
            if (Schema::hasColumn('quota_years', 'ativo')) {
                $table->dropColumn('ativo');
            }

            if (Schema::hasColumn('quota_years', 'valor')) {
                $table->dropColumn('valor');
            }
        });
    }
};