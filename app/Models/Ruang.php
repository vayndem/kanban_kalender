<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruang extends Model
{
    use HasFactory; // <-- DITAMBAHKAN

    protected $fillable = ['name'];

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'ruang_id', 'id');
    }
}
