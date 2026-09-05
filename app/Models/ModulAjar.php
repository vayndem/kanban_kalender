<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModulAjar extends Model
{
    protected $fillable = [
        'kode_kelas',
        'tujuan_pembelajaran',
        'kompetensi_awal',
        'model_pembelajaran',
        'sarana_media',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(ModulAjarDetail::class);
    }
}
