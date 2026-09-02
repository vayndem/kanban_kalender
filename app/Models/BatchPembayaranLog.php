<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchPembayaranLog extends Model
{
    use HasFactory;

    public const JENIS_PENAGIHAN = 'penagihan_massal';

    public const JENIS_PELUNASAN = 'pelunasan_massal';

    protected $fillable = [
        'jenis',
        'periode',
        'jumlah_diproses',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diproses' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            self::JENIS_PENAGIHAN => 'Penagihan massal',
            self::JENIS_PELUNASAN => 'Pelunasan massal',
            default => $this->jenis,
        };
    }
}
