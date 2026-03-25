<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socios', function (Blueprint $table): void {
            if (! Schema::hasColumn('socios', 'wp_user_id')) {
                $table->unsignedBigInteger('wp_user_id')->nullable()->after('email');
                $table->unique('wp_user_id', 'socios_wp_user_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table): void {
            if (Schema::hasColumn('socios', 'wp_user_id')) {
                $table->dropUnique('socios_wp_user_id_unique');
                $table->dropColumn('wp_user_id');
            }
        });
    }
};

