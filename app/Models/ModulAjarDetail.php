<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModulAjarDetail extends Model
{
    protected $fillable = [
        'modul_ajar_id',
        'materi',
        'sub_materi',
        'cara_mengajar',
        'tugas',
        'tujuan',
        'hasil_akhir_pembelajaran',
        'keterangan',
        'sedang_dipersiapkan',
        'guru_pengganti_id',
        'diajarkan_oleh_guru_id',
        'tanggal_diajarkan',
    ];

    protected $casts = [
        'sedang_dipersiapkan' => 'boolean',
        'tanggal_diajarkan' => 'date',
    ];

    public function modulAjar(): BelongsTo
    {
        return $this->belongsTo(ModulAjar::class);
    }

    public function guruPengganti(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_pengganti_id');
    }

    public function diajarkanOlehGuru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'diajarkan_oleh_guru_id');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(ModulAjarAbsensi::class);
    }

    public function absensiGuru(): HasOne
    {
        return $this->hasOne(AbsensiGuru::class);
    }
}
