<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArsipStatisAnggota extends Model
{
    protected $table = 'arsip_statis_anggota';

    protected $fillable = [
        'arsip_statis_id', 'nama', 'nip',
    ];

    public function arsip(): BelongsTo
    {
        return $this->belongsTo(ArsipStatis::class);
    }
}
