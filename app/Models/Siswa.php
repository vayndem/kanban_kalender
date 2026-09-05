<?php

namespace App\Models;

use App\Models\Concerns\MenormalisasiNoHp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    use HasFactory, MenormalisasiNoHp;

    protected $fillable = [
        'name',
        'panggilan',
        'kelas',
        'no_hp',
        'paket_pembayaran',
        'paket_pembayaran_2',
        'paket_pembayaran_3',
        'paket_pembayaran_4',
        'paket_pembayaran_5',
        'tingkat_kemampuan_id',
    ];

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'siswa_id', 'id');
    }

    public function tandas(): HasMany
    {
        return $this->hasMany(Tanda::class, 'siswa_id', 'id');
    }

    public function paket(): BelongsTo
    {
        return $this->belongsTo(Paket::class, 'paket_pembayaran');
    }

    public function tingkatKemampuan(): BelongsTo
    {
        return $this->belongsTo(TingkatKemampuan::class, 'tingkat_kemampuan_id');
    }
}
