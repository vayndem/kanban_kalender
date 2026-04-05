<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Siswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'panggilan',
        'kelas',
        'no_hp',
        'paket_pembayaran'
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'siswa_id', 'id');
    }

    public function tandas()
    {
        return $this->hasMany(Tanda::class, 'siswa_id', 'id');
    }

    public function paket()
    {
        return $this->belongsTo(Paket::class, 'paket_pembayaran');
    }
}
