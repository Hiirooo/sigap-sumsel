<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArsipStatis extends Model
{
    protected $appends = ['file_url'];

    protected $fillable = [
        'judul', 'deskripsi', 'asal_dokumen',
        'tanggal_asli', 'file_path', 'jenis_asli', 'is_kolektif'
    ];

    public function anggota(): HasMany
    {
        return $this->hasMany(ArsipStatisAnggota::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? route('secure-files.arsip', $this) : null;
    }
}
