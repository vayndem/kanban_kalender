<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guru extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'gaji_bawaan', 'gaji_per_kehadiran'];

    protected $casts = [
        'gaji_bawaan' => 'integer',
        'gaji_per_kehadiran' => 'integer',
    ];

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'guru_id', 'id');
    }

    public function penggajians(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'guru_id', 'id');
    }

    public function absensiGurus(): HasMany
    {
        return $this->hasMany(AbsensiGuru::class, 'guru_id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function punyaAkun(): bool
    {
        return $this->user()->exists();
    }

    public function siapDibuatkanAkun(): bool
    {
        return filled($this->email) && ! $this->punyaAkun();
    }
}
