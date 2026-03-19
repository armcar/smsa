<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('socio_types', function (Blueprint $table) {
            if (Schema::hasColumn('socio_types', 'quota_valor_anual')) {
                $table->dropColumn('quota_valor_anual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('socio_types', function (Blueprint $table) {
            $table->decimal('quota_valor_anual', 8, 2)->default(0);
        });
    }
};