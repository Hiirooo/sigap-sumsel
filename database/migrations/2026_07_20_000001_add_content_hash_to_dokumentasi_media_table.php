<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumentasi_media', function (Blueprint $table) {
            $table->char('content_hash', 64)->nullable()->after('size');
            $table->unique(['dokumentasi_id', 'content_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi_media', function (Blueprint $table) {
            $table->dropUnique(['dokumentasi_id', 'content_hash']);
            $table->dropColumn('content_hash');
        });
    }
};
