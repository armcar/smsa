<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socios', function (Blueprint $table): void {
            $table->boolean('is_instrumentista')->default(false)->after('estado');
            $table->string('instrumento', 120)->nullable()->after('is_instrumentista');
            $table->date('instrumento_desde')->nullable()->after('instrumento');
            $table->date('instrumento_ate')->nullable()->after('instrumento_desde');

            $table->index('is_instrumentista');
        });
    }

    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table): void {
            $table->dropIndex(['is_instrumentista']);
            $table->dropColumn([
                'is_instrumentista',
                'instrumento',
                'instrumento_desde',
                'instrumento_ate',
            ]);
        });
    }
};

