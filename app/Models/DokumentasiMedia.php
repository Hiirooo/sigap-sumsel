<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumentasiMedia extends Model
{
    protected $table = 'dokumentasi_media';

    protected $fillable = [
        'dokumentasi_id', 'jenis_media', 'file_path', 'thumbnail_path',
        'original_name', 'size', 'urutan',
    ];

    protected $casts = [
        'size' => 'integer',
        'urutan' => 'integer',
    ];

    public function dokumentasi(): BelongsTo
    {
        return $this->belongsTo(Dokumentasi::class);
    }
}
