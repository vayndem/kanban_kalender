<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Siswa extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'siswa_id', 'id');
    }
}
