<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TingkatKemampuan extends Model
{
    protected $fillable = ['keterangan'];

    protected static function booted(): void
    {
        static::deleting(function (TingkatKemampuan $kemampuan) {
            if ($kemampuan->siswas()->exists()) {
                throw new \RuntimeException("Kemampuan \"{$kemampuan->keterangan}\" tidak bisa dihapus: masih dipakai siswa.");
            }
        });
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'tingkat_kemampuan_id');
    }
}
