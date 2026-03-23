<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wp_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('wp_applications', 'payload_hash')) {
                $table->string('payload_hash', 64)->nullable()->after('external_id');
                $table->index(['source', 'kind', 'payload_hash'], 'wp_apps_source_kind_payload_hash_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wp_applications', function (Blueprint $table) {
            if (Schema::hasColumn('wp_applications', 'payload_hash')) {
                $table->dropIndex('wp_apps_source_kind_payload_hash_idx');
                $table->dropColumn('payload_hash');
            }
        });
    }
};

