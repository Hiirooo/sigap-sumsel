<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasi_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumentasi_id')->constrained('dokumentasis')->cascadeOnDelete();
            $table->enum('jenis_media', ['foto', 'video']);
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        DB::table('dokumentasis')
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->each(function ($item) {
                DB::table('dokumentasi_media')->insert([
                    'dokumentasi_id' => $item->id,
                    'jenis_media' => $item->jenis_media,
                    'file_path' => $item->file_path,
                    'thumbnail_path' => $item->thumbnail_path,
                    'urutan' => 0,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_media');
    }
};
