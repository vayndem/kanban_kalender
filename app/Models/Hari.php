<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hari extends Model
{
    use HasFactory;

    public const NAMA_ISO = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    protected $fillable = ['name'];

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'hari_id', 'id');
    }

    public static function idHariIni(): int
    {
        $iso = (int) now()->isoFormat('E');

        return static::idUntukNomorIso($iso);
    }

    public static function idUntukNomorIso(int $iso): int
    {
        $nama = self::NAMA_ISO[$iso] ?? null;

        if ($nama !== null) {
            $id = static::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($nama)])->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return $iso;
    }
}
