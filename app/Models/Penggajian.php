<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penggajian extends Model
{
    protected $fillable = [
        'guru_id',
        'jumlah_kehadiran',
        'gaji_bawaan',
        'gaji_per_kehadiran',
        'total',
        'dijalankan_oleh',
        'dijalankan_pada',
        'dibatalkan_oleh',
        'dibatalkan_pada',
        'alasan_batal',
    ];

    protected $casts = [
        'jumlah_kehadiran' => 'integer',
        'gaji_bawaan' => 'integer',
        'gaji_per_kehadiran' => 'integer',
        'total' => 'integer',
        'dijalankan_pada' => 'datetime',
        'dibatalkan_pada' => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function dijalankanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dijalankan_oleh');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function absensiGurus(): HasMany
    {
        return $this->hasMany(AbsensiGuru::class, 'penggajian_id', 'id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNull('dibatalkan_pada');
    }

    public function sudahDibatalkan(): bool
    {
        return $this->dibatalkan_pada !== null;
    }
}
