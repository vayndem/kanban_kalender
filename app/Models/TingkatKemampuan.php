<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TingkatKemampuan extends Model
{
    protected $fillable = ['level', 'keterangan'];

    protected static function booted(): void
    {
        static::creating(function (TingkatKemampuan $kemampuan) {
            if (! isset($kemampuan->attributes['level'])) {
                $kemampuan->level = (int) (static::max('level') ?? 0) + 1;
            }
        });

        static::deleting(function (TingkatKemampuan $kemampuan) {
            if (! $kemampuan->levelTertinggi()) {
                throw new \RuntimeException("Level {$kemampuan->level} tidak bisa dihapus dulu: hapus level tertinggi dulu, baru turun satu-satu.");
            }

            if ($kemampuan->siswas()->exists()) {
                throw new \RuntimeException("Level {$kemampuan->level} tidak bisa dihapus: masih dipakai siswa.");
            }
        });
    }

    public function levelTertinggi(): bool
    {
        return $this->level === (int) static::max('level');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'tingkat_kemampuan_id');
    }
}
