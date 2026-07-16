<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monev_checklists', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('periode')->nullable();
            $table->string('aspek');
            $table->string('indikator');
            $table->string('target')->nullable();
            $table->string('realisasi')->nullable();
            $table->unsignedTinyInteger('skor')->default(0);
            $table->enum('status', ['sesuai', 'perlu_perhatian', 'kritis'])->default('perlu_perhatian');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->text('catatan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->date('tenggat_tindak_lanjut')->nullable();
            $table->enum('status_tindak_lanjut', ['belum', 'proses', 'selesai'])->default('belum');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monev_checklists');
    }
};
