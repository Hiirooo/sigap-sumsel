<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RilisBerita extends Model
{
    protected $fillable = [
        'judul', 'slug', 'isi', 'tanggal_rilis',
        'penulis', 'media_publikasi', 'gambar_utama', 'gambar_pendukung', 'sumber_url', 'status', 'is_archived',
    ];

    protected $casts = [
        'gambar_pendukung' => 'array',
        'is_archived' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (RilisBerita $rilisBerita) {
            $rilisBerita->is_archived = $rilisBerita->status === 'terpublikasi';
        });
    }
}
