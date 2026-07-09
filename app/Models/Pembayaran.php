<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembayaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_siswa',
        'harga',
        'keterangan',
        'tanggal_pembayaran',
        'pembayaran_via',
        'status',
        'no_hp',
        'total_pembayaran',
        'total_sudah_dibayar',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PembayaranDetail::class, 'id_pembayaran');
    }
}
