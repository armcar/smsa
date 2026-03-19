<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_applications', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40)->default('wordpress');
            $table->string('kind', 40); // socio | escola
            $table->string('external_id', 64);
            $table->string('status', 40)->default('pendente'); // pendente | validada | rejeitada
            $table->string('display_name')->nullable();
            $table->string('display_email')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('payload');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('wp_status_callback_url')->nullable();
            $table->timestamp('last_callback_at')->nullable();
            $table->text('last_callback_response')->nullable();
            $table->timestamps();

            $table->unique(['source', 'kind', 'external_id']);
            $table->index(['status', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_applications');
    }
};

