<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RilisBerita extends Model
{
    protected $fillable = [
        'judul', 'slug', 'isi', 'tanggal_rilis',
        'penulis', 'media_publikasi', 'gambar_utama', 'gambar_pendukung', 'sumber_url', 'status'
    ];

    protected $casts = [
        'gambar_pendukung' => 'array',
    ];
}
