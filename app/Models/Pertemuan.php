<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pertemuan extends Model
{
    use HasFactory;

    protected $table = 'pertemuans';

    protected $fillable = [
        'modul_ajar_detail_id',
        'tanggal',
        'guru_id',
        'guru_pengganti_id',
        'selesai_pada',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'selesai_pada' => 'datetime',
    ];

    public function modulAjarDetail(): BelongsTo
    {
        return $this->belongsTo(ModulAjarDetail::class, 'modul_ajar_detail_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function guruPengganti(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_pengganti_id');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(ModulAjarAbsensi::class, 'pertemuan_id');
    }

    public function absensiGuru(): HasOne
    {
        return $this->hasOne(AbsensiGuru::class, 'pertemuan_id');
    }

    public function scopeSelesai($query)
    {
        return $query->whereNotNull('selesai_pada');
    }

    public function scopeBerlangsung($query)
    {
        return $query->whereNull('selesai_pada');
    }

    public function sedangBerlangsung(): bool
    {
        return $this->selesai_pada === null;
    }
}
