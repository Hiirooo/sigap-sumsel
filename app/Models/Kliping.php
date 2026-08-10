<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kliping extends Model
{
    protected $appends = ['file_url'];

    protected $fillable = [
        'judul', 'media', 'tanggal',
        'file_path', 'url', 'isi_berita', 'sentimen',
        'sentimen_confidence', 'sentimen_otomatis',
        'sentimen_metode', 'sentimen_model', 'terkait_pimpinan',
        'persentase_keterkaitan', 'tingkat_keterkaitan', 'kata_kunci_keterkaitan', 'status', 'is_archived'
    ];

    protected $casts = [
        'sentimen_otomatis' => 'boolean',
        'terkait_pimpinan' => 'boolean',
        'persentase_keterkaitan' => 'integer',
        'is_archived' => 'boolean',
    ];

    public function getFileUrlAttribute(): ?string
    {
        return ($this->file_path || $this->url) ? route('secure-files.kliping', $this) : null;
    }
}
