<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiAspek extends Model
{
    protected $table = 'nilai_aspeks';

    protected $fillable = ['modul_ajar_absensi_id', 'aspek_penilaian_id', 'skor'];

    protected $casts = ['skor' => 'integer'];

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(ModulAjarAbsensi::class, 'modul_ajar_absensi_id');
    }

    public function aspek(): BelongsTo
    {
        return $this->belongsTo(AspekPenilaian::class, 'aspek_penilaian_id');
    }
}
