<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quota_charges', function (Blueprint $table) {
            // se já existir a coluna, ignora (opcional)
            if (! Schema::hasColumn('quota_charges', 'metodo_pagamento')) {
                $table->enum('metodo_pagamento', ['mbway', 'transferencia', 'dinheiro'])
                    ->nullable()
                    ->after('estado'); // ajusta o "after" ao teu esquema
            }
        });
    }

    public function down(): void
    {
        Schema::table('quota_charges', function (Blueprint $table) {
            if (Schema::hasColumn('quota_charges', 'metodo_pagamento')) {
                $table->dropColumn('metodo_pagamento');
            }
        });
    }
};