<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsip_statis', function (Blueprint $table) {
            $table->boolean('is_kolektif')->default(false)->after('jenis_asli');
        });

        Schema::create('arsip_statis_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arsip_statis_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->timestamps();
        });

        DB::table('arsip_statis')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $detail = json_decode((string) $row->deskripsi, true);

                if (! is_array($detail) || ! isset($detail['nama'])) {
                    continue;
                }

                DB::table('arsip_statis_anggota')->insert([
                    'arsip_statis_id' => $row->id,
                    'nama' => $detail['nama'],
                    'nip' => $detail['nip'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_statis_anggota');

        Schema::table('arsip_statis', function (Blueprint $table) {
            $table->dropColumn('is_kolektif');
        });
    }
};
