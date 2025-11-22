<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tanda extends Model
{
    // Mendefinisikan nama tabel secara eksplisit (opsional, tapi bagus untuk kepastian)
    protected $table = 'tandas';

    // Kolom yang boleh diisi (mass assignment)
    protected $fillable = [
        'siswa_id',
        'keterangan',
    ];

    /**
     * Relasi ke model Siswa (asumsi nama modelnya Siswa)
     * Ini mempermudah pemanggilan seperti: $tanda->siswa->name
     */
    public function siswa()
    {
        // Parameter kedua 'siswa_id' dan ketiga 'id' memastikan relasi membaca kolom yang benar
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
