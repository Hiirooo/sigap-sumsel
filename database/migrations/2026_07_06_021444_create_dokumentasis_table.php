<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dokumentasis', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->date('tanggal');
            $table->enum('jenis_media', ['foto', 'video']);
            $table->string('file_path');
            $table->string('pimpinan_terkait')->nullable();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_kegiatans')->onDelete('set null');
            $table->enum('status_verifikasi', ['draft', 'terverifikasi'])->default('draft');
            $table->enum('status_digitalisasi', ['belum_didigitalisasi', 'sudah_didigitalisasi', 'sudah_diarsipkan'])->default('belum_didigitalisasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumentasis');
    }
};
