<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesi extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_time', 'end_time'];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'sesi_id', 'id');
    }
}
