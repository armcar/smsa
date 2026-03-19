<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up(): void
{
    Schema::table('socios', function (Blueprint $table) {
        $table->string('numero_fiscal', 20)->nullable()->change();
    });
}

    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->string('numero_fiscal', 255)->nullable()->change();
        });
    }
};
