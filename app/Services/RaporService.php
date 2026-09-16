<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\ModulAjarAbsensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RaporService
{
    private const MINIMAL_NILAI_UNTUK_TREN = 4;

    private const AMBANG_TREN = 0.25;

    public function untukSiswa(Siswa $siswa, ?string $dari = null, ?string $sampai = null): array
    {
        $absensis = $this->ambilAbsensi($siswa, $dari, $sampai);
        $kelasInfo = $this->infoKelas($absensis);

        return [
            'siswa' => [
                'id' => $siswa->id,
                'nama' => $siswa->name,
                'panggilan' => $siswa->panggilan,
                'kelas' => $siswa->kelas,
                'kemampuan' => $siswa->tingkatKemampuan
                    ? 'Level '.$siswa->tingkatKemampuan->level.' — '.$siswa->tingkatKemampuan->keterangan
                    : null,
            ],
            'periode' => [
                'dari' => $dari,
                'sampai' => $sampai,
            ],
            'ringkasan' => $this->ringkasan($absensis),
            'per_aspek' => $this->perAspek($absensis),
            'per_mapel' => $this->perMapel($absensis, $kelasInfo),
        ];
    }

    private function ambilAbsensi(Siswa $siswa, ?string $dari, ?string $sampai): Collection
    {
        $awal = $dari ? Carbon::parse($dari)->startOfDay() : null;
        $akhir = $sampai ? Carbon::parse($sampai)->endOfDay() : null;

        return ModulAjarAbsensi::query()
            ->where('siswa_id', $siswa->id)
            ->with([
                'modulAjarDetail:id,modul_ajar_id,materi,sub_materi,tanggal_diajarkan,diajarkan_oleh_guru_id',
                'modulAjarDetail.modulAjar:id,kode_kelas',
                'modulAjarDetail.diajarkanOlehGuru:id,name',
                'nilaiAspeks.aspek:id,nama,indikator,urutan',
            ])
            ->get()
            ->filter(fn (ModulAjarAbsensi $a) => $a->modulAjarDetail?->tanggal_diajarkan !== null)
            ->filter(function (ModulAjarAbsensi $a) use ($awal, $akhir) {
                $tanggal = $a->modulAjarDetail->tanggal_diajarkan;

                return ! ($awal && $tanggal->lt($awal)) && ! ($akhir && $tanggal->gt($akhir));
            })
            ->sortBy(fn (ModulAjarAbsensi $a) => $a->modulAjarDetail->tanggal_diajarkan->toDateString())
            ->values();
    }

    private function infoKelas(Collection $absensis): Collection
    {
        $kodeKelas = $absensis
            ->map(fn (ModulAjarAbsensi $a) => $a->modulAjarDetail->modulAjar?->kode_kelas)
            ->filter()
            ->unique();

        if ($kodeKelas->isEmpty()) {
            return collect();
        }

        return Jadwal::query()
            ->whereIn('kode_kelas', $kodeKelas)
            ->with(['mataPelajaran:id,name', 'guru:id,name'])
            ->get(['id', 'kode_kelas', 'mata_pelajaran_id', 'guru_id'])
            ->groupBy('kode_kelas')
            ->map(fn ($rows) => [
                'mapel' => $rows->first()->mataPelajaran?->name ?? '-',
                'guru' => $rows->first()->guru?->name ?? '-',
            ]);
    }

    private function nilaiPertemuan(Collection $absensis): Collection
    {
        return $absensis
            ->where('hadir', true)
            ->map(fn (ModulAjarAbsensi $a) => $a->rataAspek())
            ->filter(fn ($n) => $n !== null)
            ->values();
    }

    private function ringkasan(Collection $absensis): array
    {
        $hadir = $absensis->where('hadir', true);
        $nilai = $this->nilaiPertemuan($absensis);
        $total = $absensis->count();

        return [
            'total_pertemuan' => $total,
            'hadir' => $hadir->count(),
            'tidak_hadir' => $total - $hadir->count(),
            'persen_kehadiran' => $total > 0 ? (int) round($hadir->count() / $total * 100) : 0,
            'rata_nilai' => $nilai->isNotEmpty() ? round($nilai->avg(), 2) : null,
            'nilai_terendah' => $nilai->isNotEmpty() ? round($nilai->min(), 2) : null,
            'nilai_tertinggi' => $nilai->isNotEmpty() ? round($nilai->max(), 2) : null,
            'tren' => $this->tren($nilai),
        ];
    }

    private function tren(Collection $nilai): ?string
    {
        if ($nilai->count() < self::MINIMAL_NILAI_UNTUK_TREN) {
            return null;
        }

        $titikTengah = (int) floor($nilai->count() / 2);
        $selisih = $nilai->slice($titikTengah)->avg() - $nilai->take($titikTengah)->avg();

        if ($selisih > self::AMBANG_TREN) {
            return 'naik';
        }

        return $selisih < -self::AMBANG_TREN ? 'turun' : 'stabil';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function perAspek(Collection $absensis): array
    {
        $skor = $absensis
            ->where('hadir', true)
            ->flatMap(fn (ModulAjarAbsensi $a) => $a->nilaiAspeks)
            ->filter(fn ($n) => $n->aspek !== null);

        if ($skor->isEmpty()) {
            return [];
        }

        return $skor
            ->groupBy('aspek_penilaian_id')
            ->map(function (Collection $rows) {
                $aspek = $rows->first()->aspek;
                $nilai = $rows->pluck('skor');

                return [
                    'aspek_id' => $aspek->id,
                    'nama' => $aspek->nama,
                    'indikator' => $aspek->indikator,
                    'urutan' => $aspek->urutan,
                    'jumlah_dinilai' => $nilai->count(),
                    'rata' => round($nilai->avg(), 2),
                    'terendah' => (int) $nilai->min(),
                    'tertinggi' => (int) $nilai->max(),
                    'tren' => $this->tren($rows->pluck('skor')->map(fn ($s) => (float) $s)->values()),
                ];
            })
            ->sortBy([['urutan', 'asc'], ['nama', 'asc']])
            ->values()
            ->all();
    }

    private function perMapel(Collection $absensis, Collection $kelasInfo): array
    {
        return $absensis
            ->groupBy(fn (ModulAjarAbsensi $a) => $a->modulAjarDetail->modulAjar?->kode_kelas ?? '-')
            ->map(function (Collection $rows, string $kodeKelas) use ($kelasInfo) {
                $hadir = $rows->where('hadir', true);
                $nilai = $this->nilaiPertemuan($rows);

                return [
                    'mapel' => $kelasInfo[$kodeKelas]['mapel'] ?? '-',
                    'guru' => $kelasInfo[$kodeKelas]['guru'] ?? '-',
                    'jumlah_pertemuan' => $rows->count(),
                    'hadir' => $hadir->count(),
                    'rata_nilai' => $nilai->isNotEmpty() ? round($nilai->avg(), 2) : null,
                    'per_aspek' => $this->perAspek($rows),
                    'pertemuan' => $rows->map(fn (ModulAjarAbsensi $a) => [
                        'tanggal' => $a->modulAjarDetail->tanggal_diajarkan->toDateString(),
                        'materi' => $a->modulAjarDetail->materi,
                        'sub_materi' => $a->modulAjarDetail->sub_materi,
                        'hadir' => (bool) $a->hadir,
                        'nilai' => $a->rataAspek(),
                        'skor_aspek' => $a->nilaiAspeks
                            ->filter(fn ($n) => $n->aspek !== null)
                            ->sortBy(fn ($n) => $n->aspek->urutan)
                            ->map(fn ($n) => ['nama' => $n->aspek->nama, 'skor' => $n->skor])
                            ->values()
                            ->all(),
                        'diajar_oleh' => $a->modulAjarDetail->diajarkanOlehGuru?->name ?? '-',
                    ])->values()->all(),
                ];
            })
            ->sortBy('mapel')
            ->values()
            ->all();
    }
}
