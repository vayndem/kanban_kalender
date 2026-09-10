<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StashPemulihanLog extends Model
{
    protected $fillable = [
        'dipulihkan_oleh',
        'jumlah_sebelum',
        'jumlah_sesudah',
        'bentrok_masuk',
        'isi_sebelum',
    ];

    protected $casts = [
        'jumlah_sebelum' => 'integer',
        'jumlah_sesudah' => 'integer',
        'bentrok_masuk' => 'integer',
    ];

    public function dipulihkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dipulihkan_oleh');
    }
}
