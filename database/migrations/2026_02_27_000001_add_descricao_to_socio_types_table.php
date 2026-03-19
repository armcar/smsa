<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socio_types', function (Blueprint $table) {
            if (! Schema::hasColumn('socio_types', 'descricao')) {
                $table->text('descricao')->nullable()->after('nome');
            }
        });
    }

    public function down(): void
    {
        Schema::table('socio_types', function (Blueprint $table) {
            if (Schema::hasColumn('socio_types', 'descricao')) {
                $table->dropColumn('descricao');
            }
        });
    }
};
