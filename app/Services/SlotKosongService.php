<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\Ruang;
use App\Models\Sesi;
use Illuminate\Support\Collection;

class SlotKosongService
{
    public function __construct(
        private readonly IrisanSesiService $irisan,
        private readonly KetersediaanGuruService $ketersediaan,
    ) {}

    /**
     * @param  Collection<int, Jadwal>  $jadwals
     * @return array<int, array<string, mixed>>
     */
    public function peta(Collection $jadwals): array
    {
        $haris = Hari::orderBy('id')->get(['id', 'name']);
        $sesis = Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']);
        $ruangs = Ruang::orderBy('name')->get(['id', 'name']);
        $gurus = Guru::orderBy('name')->get(['id', 'name']);

        $peta = [];

        foreach ($haris as $hari) {
            foreach ($sesis as $sesi) {
                $baris = $this->hitungSlot($jadwals, $hari->id, $sesi, $ruangs, $gurus);

                $peta[] = [
                    'hari' => $hari->name,
                    'sesi' => $sesi->name.' - '.$sesi->start_time.'–'.$sesi->end_time,
                    'kelas_berjalan' => $baris['kelas_berjalan'],
                    'ruang_kosong' => $baris['ruang_kosong'],
                    'guru_kosong' => $baris['guru_kosong'],
                ];
            }
        }

        return $peta;
    }

    /**
     * @return array<string, mixed>
     */
    public function hariIni(): array
    {
        $hariId = Hari::idHariIni();
        $hari = $hariId ? Hari::find($hariId) : null;

        $sesis = Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']);
        $ruangs = Ruang::orderBy('name')->get(['id', 'name']);
        $gurus = Guru::orderBy('name')->get(['id', 'name']);

        if (! $hari || $sesis->isEmpty()) {
            return [
                'ada' => false,
                'hari' => $hari?->name,
                'total_ruang' => $ruangs->count(),
                'total_guru' => $gurus->count(),
                'sesi' => collect(),
                'sesi_kosong' => 0,
                'sesi_penuh' => 0,
            ];
        }

        $jadwals = Jadwal::query()
            ->where('hari_id', $hari->id)
            ->get(['id', 'hari_id', 'sesi_id', 'guru_id', 'ruang_id', 'siswa_id']);

        $sesiRinci = $sesis->map(function (Sesi $sesi) use ($jadwals, $hari, $ruangs, $gurus) {
            $baris = $this->hitungSlot($jadwals, $hari->id, $sesi, $ruangs, $gurus);
            $ruangKosong = $baris['ruang_kosong'];
            $terpakai = $ruangs->count() - $ruangKosong->count();

            return [
                'sesi_id' => $sesi->id,
                'nama' => $sesi->name,
                'jam' => $sesi->start_time.'–'.$sesi->end_time,
                'mulai' => $sesi->start_time,
                'kelas_berjalan' => $baris['kelas_berjalan'],
                'ruang_kosong' => $ruangKosong,
                'ruang_terpakai' => $terpakai,
                'guru_kosong' => $baris['guru_kosong'],
                'guru_berhalangan' => $baris['guru_berhalangan'],
                'persen_ruang_terpakai' => $ruangs->count() > 0
                    ? (int) round($terpakai / $ruangs->count() * 100)
                    : 0,
                'status' => $this->status($baris['kelas_berjalan'], $ruangKosong->count(), $baris['guru_kosong']->count()),
                'ruang_dipakai_sesi_lain' => $baris['kelas_berjalan'] === 0 && $terpakai > 0,
            ];
        })->values();

        return [
            'ada' => true,
            'hari' => $hari->name,
            'total_ruang' => $ruangs->count(),
            'total_guru' => $gurus->count(),
            'sesi' => $sesiRinci,
            'sesi_kosong' => $sesiRinci->where('status', 'kosong')->count(),
            'sesi_penuh' => $sesiRinci->where('status', 'penuh')->count(),
        ];
    }

    /**
     * @param  Collection<int, Jadwal>  $jadwals
     * @param  Collection<int, Ruang>  $ruangs
     * @param  Collection<int, Guru>  $gurus
     * @return array<string, mixed>
     */
    private function hitungSlot(Collection $jadwals, int $hariId, Sesi $sesi, Collection $ruangs, Collection $gurus): array
    {
        $diHari = $jadwals->where('hari_id', $hariId);
        $diSlot = $diHari->where('sesi_id', $sesi->id);
        $diWaktuIni = $diHari->whereIn('sesi_id', $this->irisan->idBeririsan($sesi->id));

        $ruangTerpakai = $diWaktuIni->pluck('ruang_id')->unique();
        $guruTerpakai = $diWaktuIni->pluck('guru_id')->unique();

        $guruBerhalangan = $gurus
            ->filter(fn (Guru $g) => $this->ketersediaan->bentrok($g->id, $hariId, $sesi->id) !== null)
            ->pluck('id');

        return [
            'kelas_berjalan' => $diSlot->unique(fn (Jadwal $j) => "{$j->ruang_id}_{$j->guru_id}")->count(),
            'ruang_kosong' => $ruangs->whereNotIn('id', $ruangTerpakai)->pluck('name')->values(),
            'guru_kosong' => $gurus
                ->whereNotIn('id', $guruTerpakai)
                ->whereNotIn('id', $guruBerhalangan)
                ->pluck('name')
                ->values(),
            'guru_berhalangan' => $guruBerhalangan->count(),
        ];
    }

    private function status(int $kelasBerjalan, int $ruangKosong, int $guruKosong): string
    {
        if ($ruangKosong === 0 || $guruKosong === 0) {
            return 'penuh';
        }

        return $kelasBerjalan === 0 ? 'kosong' : 'longgar';
    }
}
