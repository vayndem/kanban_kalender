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

    protected $appends = ['status_label', 'status_color'];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'harga' => 'integer',
            'total_sudah_dibayar' => 'integer',
            'tanggal_pembayaran' => 'date',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            1 => 'Tertagih',
            2 => 'Lunas',
            default => 'Belum Bayar',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ((int) $this->status) {
            1 => 'amber',
            2 => 'emerald',
            default => 'rose',
        };
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PembayaranDetail::class, 'id_pembayaran');
    }
}
