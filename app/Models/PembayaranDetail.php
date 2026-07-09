<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranDetail extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_details';

    protected $fillable = [
        'id_pembayaran',
        'pembayaran',
        'keterangan',
    ];

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'id_pembayaran');
    }
}
