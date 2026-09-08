<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesi extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_time', 'end_time'];

    public function getStartTimeAttribute(?string $value): ?string
    {
        return $this->jamSaja($value);
    }

    public function getEndTimeAttribute(?string $value): ?string
    {
        return $this->jamSaja($value);
    }

    public function getRentangJamAttribute(): string
    {
        return $this->start_time && $this->end_time
            ? $this->start_time.'–'.$this->end_time
            : '';
    }

    public function getLabelAttribute(): string
    {
        $rentang = $this->rentang_jam;

        return $rentang === '' ? (string) $this->name : $this->name.' - '.$rentang;
    }

    private function jamSaja(?string $value): ?string
    {
        return $value === null || $value === '' ? null : substr($value, 0, 5);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'sesi_id', 'id');
    }
}
