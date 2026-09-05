<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModulAjarAbsensi extends Model
{
    protected $table = 'modul_ajar_absensis';

    protected $fillable = [
        'modul_ajar_detail_id',
        'siswa_id',
        'hadir',
        'nilai',
    ];

    protected $casts = [
        'hadir' => 'boolean',
    ];

    public function modulAjarDetail(): BelongsTo
    {
        return $this->belongsTo(ModulAjarDetail::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
