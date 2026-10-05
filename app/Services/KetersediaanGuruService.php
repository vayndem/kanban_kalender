<?php

namespace App\Services;

use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\KetersediaanGuru;
use App\Models\Sesi;
use Illuminate\Support\Collection;

class KetersediaanGuruService
{
    public function bentrok(int $guruId, int $hariId, int $sesiId): ?KetersediaanGuru
    {
        $sesi = Sesi::find($sesiId);

        if (! $sesi) {
            return null;
        }

        return KetersediaanGuru::query()
            ->where('guru_id', $guruId)
            ->where('hari_id', $hariId)
            ->with('guru:id,name', 'hari:id,name')
            ->get()
            ->first(fn (KetersediaanGuru $k) => $this->beririsan(
                $sesi->start_time,
                $sesi->end_time,
                $k->jam_mulai,
                $k->jam_selesai
            ));
    }

    public function pesan(KetersediaanGuru $halangan, int $sesiId): string
    {
        $sesi = Sesi::find($sesiId);
        $alasan = filled($halangan->alasan) ? " (alasan: {$halangan->alasan})" : '';

        return sprintf(
            '%s ditandai tidak tersedia pada %s %s%s, sedangkan sesi %s berjalan %s.',
            $halangan->guru?->name ?? 'Guru ini',
            $halangan->hari?->name ?? '-',
            $halangan->rentang,
            $alasan,
            $sesi?->name ?? '-',
            $sesi ? $sesi->start_time.'–'.$sesi->end_time : '-'
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function daftarPerGuru(): Collection
    {
        $namaHari = Hari::pluck('name', 'id');

        return KetersediaanGuru::query()
            ->with('guru:id,name')
            ->orderBy('hari_id')
            ->orderBy('jam_mulai')
            ->get()
            ->map(fn (KetersediaanGuru $k) => [
                'id' => $k->id,
                'guru_id' => $k->guru_id,
                'guru' => $k->guru?->name ?? '-',
                'hari_id' => $k->hari_id,
                'hari' => $namaHari[$k->hari_id] ?? '-',
                'jam_mulai' => $k->jam_mulai,
                'jam_selesai' => $k->jam_selesai,
                'rentang' => $k->rentang,
                'alasan' => $k->alasan,
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function jadwalYangMelanggar(): Collection
    {
        $halangan = KetersediaanGuru::query()->with('guru:id,name', 'hari:id,name')->get();

        if ($halangan->isEmpty()) {
            return collect();
        }

        $sesis = Sesi::all()->keyBy('id');

        return Jadwal::query()
            ->with(['mataPelajaran:id,name', 'guru:id,name', 'ruang:id,name', 'hari:id,name'])
            ->get(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id', 'kode_kelas'])
            ->groupBy('kode_kelas')
            ->map(function ($baris) use ($halangan, $sesis) {
                $pertama = $baris->first();
                $sesi = $sesis->get($pertama->sesi_id);

                if (! $sesi) {
                    return null;
                }

                $tabrak = $halangan->first(fn (KetersediaanGuru $k) => $k->guru_id === $pertama->guru_id
                    && $k->hari_id === $pertama->hari_id
                    && $this->beririsan($sesi->start_time, $sesi->end_time, $k->jam_mulai, $k->jam_selesai));

                if (! $tabrak) {
                    return null;
                }

                return [
                    'kode_kelas' => $pertama->kode_kelas,
                    'mapel' => $pertama->mataPelajaran?->name ?? '-',
                    'guru' => $pertama->guru?->name ?? '-',
                    'hari' => $pertama->hari?->name ?? '-',
                    'sesi' => $sesi->name,
                    'jam' => $sesi->start_time.'–'.$sesi->end_time,
                    'ruang' => $pertama->ruang?->name ?? '-',
                    'jumlah_siswa' => $baris->pluck('siswa_id')->unique()->count(),
                    'tidak_tersedia' => $tabrak->rentang,
                    'alasan' => $tabrak->alasan,
                ];
            })
            ->filter()
            ->values();
    }

    private function beririsan(string $mulaiA, string $selesaiA, string $mulaiB, string $selesaiB): bool
    {
        return $mulaiA < $selesaiB && $mulaiB < $selesaiA;
    }
}
