<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Penggajian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    public const JEDA_ANTI_GANDA = 180;

    public function ringkasan(): Collection
    {
        $belumDibayar = AbsensiGuru::query()
            ->whereNull('penggajian_id')
            ->selectRaw('guru_id, count(*) as jumlah')
            ->groupBy('guru_id')
            ->pluck('jumlah', 'guru_id');

        $strukTerakhir = Penggajian::query()
            ->aktif()
            ->orderByDesc('dijalankan_pada')
            ->get()
            ->unique('guru_id')
            ->keyBy('guru_id');

        return Guru::query()->orderBy('name')->get()->map(function (Guru $guru) use ($belumDibayar, $strukTerakhir) {
            $kehadiran = (int) $belumDibayar->get($guru->id, 0);
            $terakhir = $strukTerakhir->get($guru->id);

            return [
                'id' => $guru->id,
                'nama' => $guru->name,
                'gaji_bawaan' => $guru->gaji_bawaan,
                'gaji_per_kehadiran' => $guru->gaji_per_kehadiran,
                'kehadiran_belum_dibayar' => $kehadiran,
                'perkiraan_total' => $this->hitungTotal($guru->gaji_bawaan, $guru->gaji_per_kehadiran, $kehadiran),
                'struk_terakhir' => $terakhir ? [
                    'id' => $terakhir->id,
                    'total' => $terakhir->total,
                    'jumlah_kehadiran' => $terakhir->jumlah_kehadiran,
                    'dijalankan_pada' => $terakhir->dijalankan_pada?->toDateTimeString(),
                ] : null,
            ];
        })->values();
    }

    public function jalankan(Guru $guru, ?User $aktor = null): Penggajian
    {
        $this->tolakJikaBaruSajaDijalankan($guru);

        return DB::transaction(function () use ($guru, $aktor) {
            $sekarang = Carbon::now();

            $struk = Penggajian::create([
                'guru_id' => $guru->id,
                'jumlah_kehadiran' => 0,
                'gaji_bawaan' => $guru->gaji_bawaan,
                'gaji_per_kehadiran' => $guru->gaji_per_kehadiran,
                'total' => $guru->gaji_bawaan,
                'dijalankan_oleh' => $aktor?->id,
                'dijalankan_pada' => $sekarang,
            ]);

            $kehadiran = DB::table('absensi_gurus')
                ->where('guru_id', $guru->id)
                ->whereNull('penggajian_id')
                ->update([
                    'penggajian_id' => $struk->id,
                    'ditutup_pada' => $sekarang,
                    'updated_at' => $sekarang,
                ]);

            $struk->update([
                'jumlah_kehadiran' => $kehadiran,
                'total' => $this->hitungTotal($struk->gaji_bawaan, $struk->gaji_per_kehadiran, $kehadiran),
            ]);

            return $struk->fresh();
        });
    }

    /**
     * @return array{struk: Collection<int, Penggajian>, dilewati: Collection<int, string>}
     */
    public function jalankanSemua(?User $aktor = null): array
    {
        $struk = collect();
        $dilewati = collect();

        foreach (Guru::query()->orderBy('name')->get() as $guru) {
            try {
                $struk->push($this->jalankan($guru, $aktor));
            } catch (RuntimeException $e) {
                $dilewati->push($guru->name.': '.$e->getMessage());
            }
        }

        return ['struk' => $struk, 'dilewati' => $dilewati];
    }

    public function batalkan(Penggajian $struk, ?User $aktor = null, ?string $alasan = null): Penggajian
    {
        if ($struk->sudahDibatalkan()) {
            throw new RuntimeException('Struk penggajian ini sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($struk, $aktor, $alasan) {
            DB::table('absensi_gurus')
                ->where('penggajian_id', $struk->id)
                ->update([
                    'penggajian_id' => null,
                    'ditutup_pada' => null,
                    'updated_at' => Carbon::now(),
                ]);

            $struk->update([
                'dibatalkan_oleh' => $aktor?->id,
                'dibatalkan_pada' => Carbon::now(),
                'alasan_batal' => $alasan,
            ]);

            return $struk->fresh();
        });
    }

    public function ringkasanGuru(Guru $guru): array
    {
        $kehadiran = AbsensiGuru::query()
            ->where('guru_id', $guru->id)
            ->whereNull('penggajian_id')
            ->count();

        return [
            'nama' => $guru->name,
            'gaji_bawaan' => $guru->gaji_bawaan,
            'gaji_per_kehadiran' => $guru->gaji_per_kehadiran,
            'kehadiran_belum_dibayar' => $kehadiran,
            'perkiraan_total' => $this->hitungTotal($guru->gaji_bawaan, $guru->gaji_per_kehadiran, $kehadiran),
            'riwayat' => Penggajian::query()
                ->where('guru_id', $guru->id)
                ->orderByDesc('dijalankan_pada')
                ->limit(24)
                ->get()
                ->map(fn (Penggajian $p) => [
                    'id' => $p->id,
                    'jumlah_kehadiran' => $p->jumlah_kehadiran,
                    'gaji_bawaan' => $p->gaji_bawaan,
                    'gaji_per_kehadiran' => $p->gaji_per_kehadiran,
                    'total' => $p->total,
                    'dijalankan_pada' => $p->dijalankan_pada?->toDateTimeString(),
                    'dibatalkan' => $p->sudahDibatalkan(),
                ])
                ->values(),
        ];
    }

    public function logKelas(Penggajian $struk): Collection
    {
        return AbsensiGuru::query()
            ->where('penggajian_id', $struk->id)
            ->with([
                'modulAjarDetail:id,modul_ajar_id,materi',
                'modulAjarDetail.modulAjar:id,kode_kelas',
            ])
            ->orderBy('tanggal')
            ->get()
            ->map(fn (AbsensiGuru $absen) => [
                'tanggal' => $absen->tanggal?->toDateString(),
                'materi' => $absen->modulAjarDetail?->materi ?? '-',
                'kode_kelas' => $absen->modulAjarDetail?->modulAjar?->kode_kelas,
            ])
            ->values();
    }

    private function hitungTotal(int $gajiBawaan, int $gajiPerKehadiran, int $kehadiran): int
    {
        return $gajiBawaan + ($gajiPerKehadiran * $kehadiran);
    }

    private function tolakJikaBaruSajaDijalankan(Guru $guru): void
    {
        $baruSaja = Penggajian::query()
            ->aktif()
            ->where('guru_id', $guru->id)
            ->where('dijalankan_pada', '>=', Carbon::now()->subSeconds(self::JEDA_ANTI_GANDA))
            ->exists();

        if ($baruSaja) {
            throw new RuntimeException('Penggajian untuk guru ini baru saja dijalankan. Tunggu beberapa menit atau batalkan struk sebelumnya.');
        }
    }
}
