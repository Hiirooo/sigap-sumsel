<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('arsip_statis_anggota');
        Schema::rename('arsip_statis', 'arsip_statis_backup');

        Schema::create('arsip_statis', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('asal_dokumen')->nullable();
            $table->date('tanggal_asli')->nullable();
            $table->string('file_path');
            $table->enum('jenis_asli', ['cuti', 'kenaikan_pangkat', 'berkala'])->default('cuti');
            $table->boolean('is_kolektif')->default(false);
            $table->timestamps();
        });

        DB::statement("INSERT INTO arsip_statis (judul, deskripsi, asal_dokumen, tanggal_asli, file_path, jenis_asli, is_kolektif, created_at, updated_at)
            SELECT judul, deskripsi, asal_dokumen, tanggal_asli, file_path, 'cuti', is_kolektif, created_at, updated_at FROM arsip_statis_backup");

        Schema::drop('arsip_statis_backup');

        Schema::create('arsip_statis_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arsip_statis_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('arsip_statis_anggota');
        Schema::rename('arsip_statis', 'arsip_statis_backup');

        Schema::create('arsip_statis', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('asal_dokumen')->nullable();
            $table->date('tanggal_asli')->nullable();
            $table->string('file_path');
            $table->enum('jenis_asli', ['fisik', 'cetak', 'cd', 'lainnya'])->default('fisik');
            $table->timestamps();
        });

        DB::statement("INSERT INTO arsip_statis (judul, deskripsi, asal_dokumen, tanggal_asli, file_path, jenis_asli, created_at, updated_at)
            SELECT judul, deskripsi, asal_dokumen, tanggal_asli, file_path, 'fisik', created_at, updated_at FROM arsip_statis_backup");

        Schema::drop('arsip_statis_backup');

        Schema::create('arsip_statis_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arsip_statis_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->timestamps();
        });
    }
};