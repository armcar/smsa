<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
   Schema::create('quota_years', function (Blueprint $table) {
        $table->id();
        $table->unsignedSmallInteger('ano')->unique();
        $table->decimal('valor', 8, 2)->default(0);
        $table->boolean('ativo')->default(false);
        $table->date('data_inicio')->nullable();
        $table->date('data_fim')->nullable();
        $table->string('nota')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_years');
    }
};
