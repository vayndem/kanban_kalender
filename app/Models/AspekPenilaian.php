<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AspekPenilaian extends Model
{
    use HasFactory;

    protected $table = 'aspek_penilaians';

    protected $fillable = ['nama', 'indikator', 'urutan', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    public function nilaiAspeks(): HasMany
    {
        return $this->hasMany(NilaiAspek::class, 'aspek_penilaian_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public static function urut()
    {
        return static::query()->orderBy('urutan')->orderBy('id');
    }

    public function sudahDipakai(): bool
    {
        return $this->nilaiAspeks()->exists();
    }
}
