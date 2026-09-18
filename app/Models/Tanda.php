<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tanda extends Model
{
    protected $table = 'tandas';

    protected $fillable = [
        'siswa_id',
        'keterangan',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
