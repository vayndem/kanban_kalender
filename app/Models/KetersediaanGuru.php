<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KetersediaanGuru extends Model
{
    protected $table = 'ketersediaan_gurus';

    protected $fillable = [
        'guru_id',
        'hari_id',
        'jam_mulai',
        'jam_selesai',
        'alasan',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function hari(): BelongsTo
    {
        return $this->belongsTo(Hari::class);
    }

    public function getJamMulaiAttribute($nilai): string
    {
        return substr((string) $nilai, 0, 5);
    }

    public function getJamSelesaiAttribute($nilai): string
    {
        return substr((string) $nilai, 0, 5);
    }

    public function getRentangAttribute(): string
    {
        return $this->jam_mulai.'–'.$this->jam_selesai;
    }
}
