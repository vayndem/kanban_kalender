<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- TAMBAHKAN
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory; // <-- TAMBAHKAN

    protected $fillable = ['name'];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'guru_id', 'id');
    }
}
