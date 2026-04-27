<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $fillable = [
        'id_siswa',
        'harga',
        'keterangan',
        'status',
        'tanggal_pembayaran',
        'pembayaran_via'
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'id_siswa');
    }
}
