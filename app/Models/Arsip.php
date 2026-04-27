<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arsip extends Model
{
    use HasFactory;

    protected $table = 'arsips';

    protected $fillable = [
        'name',
        'panggilan',
        'kelas',
        'no_hp',
        'paket_pembayaran',
    ];
}
