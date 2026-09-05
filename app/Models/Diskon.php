<?php

namespace App\Models;

use App\Models\Concerns\MenormalisasiNoHp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diskon extends Model
{
    use HasFactory, MenormalisasiNoHp;

    protected $fillable = [
        'no_hp',
        'diskon',
        'keterangan',
    ];
}
