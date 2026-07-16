<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArsipStatis extends Model
{
    protected $appends = ['file_url'];

    protected $fillable = [
        'judul', 'deskripsi', 'asal_dokumen',
        'tanggal_asli', 'file_path', 'jenis_asli'
    ];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? route('secure-files.arsip', $this) : null;
    }
}
