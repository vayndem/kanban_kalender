<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGuru extends Model
{
    protected $fillable = [
        'guru_id',
        'pertemuan_id',
        'penggajian_id',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'ditutup_pada' => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class, 'pertemuan_id');
    }

    public function penggajian(): BelongsTo
    {
        return $this->belongsTo(Penggajian::class);
    }
}
