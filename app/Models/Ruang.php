<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ruang extends Model
{
    use HasFactory; // <-- DITAMBAHKAN

    protected $fillable = ['name'];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'ruang_id', 'id');
    }
}
