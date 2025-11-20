<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- DITAMBAHKAN
use Illuminate\Database\Eloquent\Model;

class Hari extends Model
{
    use HasFactory; // <-- DITAMBAHKAN

    protected $fillable = ['name'];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'hari_id', 'id');
    }
}
