<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('url')->nullable()->after('tanggal');
            $table->longText('isi_berita')->nullable()->after('url');
            $table->unsignedTinyInteger('sentimen_confidence')->nullable()->after('sentimen');
            $table->boolean('sentimen_otomatis')->default(false)->after('sentimen_confidence');
            $table->boolean('terkait_pimpinan')->default(false)->after('sentimen_otomatis');
            $table->string('kata_kunci_keterkaitan')->nullable()->after('terkait_pimpinan');
        });
    }

    public function down(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            $table->string('file_path')->nullable(false)->change();
            $table->dropColumn([
                'url',
                'isi_berita',
                'sentimen_confidence',
                'sentimen_otomatis',
                'terkait_pimpinan',
                'kata_kunci_keterkaitan',
            ]);
        });
    }
};
