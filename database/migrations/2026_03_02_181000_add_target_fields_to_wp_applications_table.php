<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wp_applications', function (Blueprint $table) {
            $table->string('target_socio_type_code', 10)->nullable()->after('imported_socio_id');
            $table->unsignedInteger('target_num_socio')->nullable()->after('target_socio_type_code');
        });
    }

    public function down(): void
    {
        Schema::table('wp_applications', function (Blueprint $table) {
            $table->dropColumn(['target_socio_type_code', 'target_num_socio']);
        });
    }
};

