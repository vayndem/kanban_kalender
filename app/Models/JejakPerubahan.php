<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JejakPerubahan extends Model
{
    protected $table = 'jejak_perubahans';

    protected $fillable = [
        'user_id',
        'nama_pelaku',
        'entitas',
        'aksi',
        'entitas_id',
        'kode_kelas',
        'ringkasan',
        'detail',
    ];

    protected $casts = [
        'detail' => 'array',
    ];

    public const ENTITAS_SISWA = 'siswa';

    public const ENTITAS_JADWAL = 'jadwal';

    public const AKSI_DIBUAT = 'dibuat';

    public const AKSI_DIUBAH = 'diubah';

    public const AKSI_DIHAPUS = 'dihapus';

    public const AKSI_DIPAKSA = 'dipaksa';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeTerbaru($query)
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
