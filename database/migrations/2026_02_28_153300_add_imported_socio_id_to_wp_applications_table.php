<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wp_applications', function (Blueprint $table) {
            $table->foreignId('imported_socio_id')
                ->nullable()
                ->after('external_id')
                ->constrained('socios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wp_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('imported_socio_id');
        });
    }
};

