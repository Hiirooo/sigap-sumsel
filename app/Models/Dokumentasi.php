<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dokumentasi extends Model
{
    protected $appends = ['file_url'];

    protected $fillable = [
        'judul', 'narasi', 'tanggal', 'jenis_media', 'file_path', 'thumbnail_path',
        'pimpinan_terkait', 'kategori_id', 'status_verifikasi', 'status_digitalisasi', 'is_archived'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? route('secure-files.dokumentasi', $this) : null;
    }

    public function mediaItems(): HasMany
    {
        return $this->hasMany(DokumentasiMedia::class)->orderBy('urutan')->orderBy('id');
    }
}
