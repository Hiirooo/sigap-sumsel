<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonevChecklist extends Model
{
    protected $fillable = [
        'tanggal',
        'periode',
        'aspek',
        'indikator',
        'target',
        'realisasi',
        'skor',
        'status',
        'prioritas',
        'catatan',
        'rekomendasi',
        'penanggung_jawab',
        'tenggat_tindak_lanjut',
        'status_tindak_lanjut',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tenggat_tindak_lanjut' => 'date',
        'skor' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
