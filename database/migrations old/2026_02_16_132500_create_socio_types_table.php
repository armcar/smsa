<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('socio_types', function (Blueprint $table) {
    //         $table->id();
    //         $table->timestamps();
    //     });
    // }

    public function up(): void
{
    Schema::create('socio_types', function (Blueprint $table) {
        $table->id();
        $table->string('nome')->unique();
        $table->decimal('quota_valor_anual', 10, 2)->default(0);
        $table->boolean('ativo')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socio_types');
    }
};
