<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- DITAMBAHKAN
use Illuminate\Database\Eloquent\Model;

class Sesi extends Model
{
    use HasFactory; // <-- DITAMBAHKAN

    protected $fillable = ['name', 'start_time', 'end_time'];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'sesi_id', 'id');
    }
}
