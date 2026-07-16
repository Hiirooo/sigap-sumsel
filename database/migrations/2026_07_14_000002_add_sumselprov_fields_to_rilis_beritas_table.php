<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rilis_beritas', function (Blueprint $table) {
            $table->string('gambar_utama')->nullable()->after('media_publikasi');
            $table->string('sumber_url', 2048)->nullable()->after('gambar_utama');
        });
    }

    public function down(): void
    {
        Schema::table('rilis_beritas', function (Blueprint $table) {
            $table->dropColumn(['gambar_utama', 'sumber_url']);
        });
    }
};
