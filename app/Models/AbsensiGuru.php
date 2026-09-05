<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGuru extends Model
{
    protected $fillable = [
        'guru_id',
        'modul_ajar_detail_id',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'ditutup_pada' => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function modulAjarDetail(): BelongsTo
    {
        return $this->belongsTo(ModulAjarDetail::class);
    }
}
