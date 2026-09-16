<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModulAjarAbsensi extends Model
{
    protected $table = 'modul_ajar_absensis';

    protected $fillable = [
        'pertemuan_id',
        'siswa_id',
        'hadir',
        'nilai',
    ];

    protected $casts = [
        'hadir' => 'boolean',
    ];

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class, 'pertemuan_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function nilaiAspeks(): HasMany
    {
        return $this->hasMany(NilaiAspek::class, 'modul_ajar_absensi_id');
    }

    public function rataAspek(): ?float
    {
        $skor = $this->nilaiAspeks->pluck('skor');

        if ($skor->isEmpty()) {
            return $this->nilai !== null ? (float) $this->nilai : null;
        }

        return round($skor->avg(), 2);
    }
}
