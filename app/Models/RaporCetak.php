<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaporCetak extends Model
{
    protected $table = 'rapor_cetaks';

    protected $fillable = [
        'siswa_id',
        'dicetak_oleh',
        'periode_label',
        'pertemuan_ids',
        'kekuatan',
        'perbaikan',
        'komentar',
        'rencana',
    ];

    protected $casts = [
        'pertemuan_ids' => 'array',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function dicetakOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicetak_oleh');
    }

    public function adaCatatan(): bool
    {
        return filled($this->kekuatan) || filled($this->perbaikan)
            || filled($this->komentar) || filled($this->rencana);
    }

    public static function terakhirUntuk(int $siswaId): ?self
    {
        return static::where('siswa_id', $siswaId)->latest('id')->first();
    }
}
