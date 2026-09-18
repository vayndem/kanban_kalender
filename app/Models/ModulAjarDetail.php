<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModulAjarDetail extends Model
{
    protected $fillable = [
        'modul_ajar_id',
        'materi',
        'sub_materi',
        'cara_mengajar',
        'tugas',
        'tujuan',
        'hasil_akhir_pembelajaran',
        'keterangan',
        'tidak_bisa_hadir',
    ];

    protected $casts = [
        'tidak_bisa_hadir' => 'boolean',
    ];

    protected $appends = [
        'sedang_dipersiapkan',
        'tanggal_diajarkan',
        'diajarkan_oleh_guru_id',
        'guru_pengganti_id',
    ];

    public function modulAjar(): BelongsTo
    {
        return $this->belongsTo(ModulAjar::class);
    }

    /**
     * @return HasMany<Pertemuan, $this>
     */
    public function pertemuans(): HasMany
    {
        return $this->hasMany(Pertemuan::class, 'modul_ajar_detail_id');
    }

    public function pertemuanBerlangsung(): HasOne
    {
        return $this->hasOne(Pertemuan::class, 'modul_ajar_detail_id')->whereNull('selesai_pada');
    }

    public function pertemuanTerakhir(): HasOne
    {
        return $this->hasOne(Pertemuan::class, 'modul_ajar_detail_id')
            ->whereNotNull('selesai_pada')
            ->latestOfMany('tanggal');
    }

    private function pertemuanSelesaiTerbaru(): ?Pertemuan
    {
        return $this->pertemuans
            ->filter(fn (Pertemuan $p) => $p->selesai_pada !== null)
            ->sortByDesc(fn (Pertemuan $p) => $p->tanggal?->toDateString())
            ->first();
    }

    public function getSedangDipersiapkanAttribute(): bool
    {
        return $this->pertemuans->contains(fn (Pertemuan $p) => $p->selesai_pada === null);
    }

    public function getTanggalDiajarkanAttribute(): ?string
    {
        return $this->pertemuanSelesaiTerbaru()?->tanggal?->toDateString();
    }

    public function getDiajarkanOlehGuruIdAttribute(): ?int
    {
        return $this->pertemuanSelesaiTerbaru()?->guru_id;
    }

    public function getGuruPenggantiIdAttribute(): ?int
    {
        return $this->pertemuans->firstWhere('selesai_pada', null)?->guru_pengganti_id;
    }

    public function getJumlahPertemuanAttribute(): int
    {
        return $this->pertemuans->filter(fn (Pertemuan $p) => $p->selesai_pada !== null)->count();
    }
}
