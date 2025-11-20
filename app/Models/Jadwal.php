<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'hari_id',
        'sesi_id',
        'mata_pelajaran_id',
        'guru_id',
        'ruang_id',
        'siswa_id',
    ];


    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }


    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id', 'id');
    }


    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id', 'id');
    }


    public function hari()
    {
        return $this->belongsTo(Hari::class, 'hari_id', 'id');
    }


    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'ruang_id', 'id');
    }


    public function sesi()
    {
        return $this->belongsTo(Sesi::class, 'sesi_id', 'id');
    }
}
