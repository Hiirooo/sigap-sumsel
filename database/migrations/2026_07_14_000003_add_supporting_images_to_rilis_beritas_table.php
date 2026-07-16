<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rilis_beritas', function (Blueprint $table) {
            $table->json('gambar_pendukung')->nullable()->after('gambar_utama');
        });
    }

    public function down(): void
    {
        Schema::table('rilis_beritas', function (Blueprint $table) {
            $table->dropColumn('gambar_pendukung');
        });
    }
};
