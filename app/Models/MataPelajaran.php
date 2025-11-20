<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    use HasFactory;

    protected $table = 'mata_pelajarans';

    protected $fillable = ['name'];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'mata_pelajaran_id', 'id');
    }

    public function getBorderColorAttribute()
    {
        $colors = ['#3b82f6', '#22c55e', '#ef4444', '#eab308', '#6366f1', '#a855f7', '#ec4899', '#f97316', '#84cc16', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#8b5cf6', '#d946ef', '#f43f5e', '#f59e0b', '#78716c', '#64748b', '#db2777'];

        $index = $this->id % count($colors);
        return $colors[$index];
    }
}
