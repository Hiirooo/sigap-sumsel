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
        'persentase_keterkaitan', 'tingkat_keterkaitan', 'kata_kunci_keterkaitan', 'status'
    ];

    protected $casts = [
        'sentimen_otomatis' => 'boolean',
        'terkait_pimpinan' => 'boolean',
        'persentase_keterkaitan' => 'integer',
    ];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? route('secure-files.kliping', $this) : null;
    }
}
