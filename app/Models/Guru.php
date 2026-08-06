<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- TAMBAHKAN
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    use HasFactory; // <-- TAMBAHKAN

    protected $fillable = ['name'];

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'guru_id', 'id');
    }
}
