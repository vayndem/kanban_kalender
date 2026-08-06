<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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


    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }


    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id', 'id');
    }


    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id', 'id');
    }


    public function hari(): BelongsTo
    {
        return $this->belongsTo(Hari::class, 'hari_id', 'id');
    }


    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class, 'ruang_id', 'id');
    }


    public function sesi(): BelongsTo
    {
        return $this->belongsTo(Sesi::class, 'sesi_id', 'id');
    }
}
