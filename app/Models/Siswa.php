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

    /**
     * Relasi ke model Tanda.
     * Ini wajib ada agar ->with('tandas') di Controller bisa jalan.
     */
    public function tandas()
    {
        // Satu siswa bisa punya banyak tanda/catatan
        return $this->hasMany(Tanda::class, 'siswa_id', 'id');
    }
}
