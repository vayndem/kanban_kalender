<?php

namespace App\Services;

use App\Models\ModulAjarAbsensi;
use App\Models\Paket;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KuotaPertemuanService
{
    private const KOLOM_PAKET = [
        'paket_pembayaran',
        'paket_pembayaran_2',
        'paket_pembayaran_3',
        'paket_pembayaran_4',
        'paket_pembayaran_5',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function perSiswa(?string $periode = null): Collection
    {
        $periode = $periode ?: Carbon::now()->format('Y-m');
        $awal = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $hargaPaket = Paket::pluck('pertemuan', 'id');
        $terpakai = $this->hadirPerSiswa($awal, $akhir);

        return Siswa::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(array_merge(['id', 'name', 'panggilan', 'kelas', 'created_at'], self::KOLOM_PAKET))
            ->map(function (Siswa $siswa) use ($hargaPaket, $terpakai, $periode) {
                $kuota = 0;

                foreach (self::KOLOM_PAKET as $kolom) {
                    $idPaket = $siswa->{$kolom};

                    if ($idPaket && $hargaPaket->has($idPaket)) {
                        $kuota += (int) $hargaPaket[$idPaket];
                    }
                }

                $hadir = (int) ($terpakai[$siswa->id] ?? 0);

                return [
                    'siswa_id' => $siswa->id,
                    'nama' => $siswa->name,
                    'kelas' => $siswa->kelas,
                    'periode' => $periode,
                    'kuota' => $kuota,
                    'hadir' => $hadir,
                    'selisih' => $hadir - $kuota,
                    'status' => $this->status($kuota, $hadir),
                ];
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function ringkasan(?string $periode = null): array
    {
        $semua = $this->perSiswa($periode);
        $berpaket = $semua->where('kuota', '>', 0);

        return [
            'periode' => $semua->first()['periode'] ?? ($periode ?: Carbon::now()->format('Y-m')),
            'periode_label' => Carbon::createFromFormat('Y-m', $semua->first()['periode'] ?? ($periode ?: Carbon::now()->format('Y-m')))
                ->locale('id')
                ->translatedFormat('F Y'),
            'kurang' => $berpaket->where('status', 'kurang')->values(),
            'lebih' => $berpaket->where('status', 'lebih')->values(),
            'pas' => $berpaket->where('status', 'pas')->count(),
            'tanpa_paket' => $semua->where('kuota', 0)->count(),
            'total_kuota' => $berpaket->sum('kuota'),
            'total_hadir' => $berpaket->sum('hadir'),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function hadirPerSiswa(Carbon $awal, Carbon $akhir): Collection
    {
        return ModulAjarAbsensi::query()
            ->where('hadir', true)
            ->with('pertemuan:id,tanggal,selesai_pada')
            ->get(['id', 'siswa_id', 'pertemuan_id'])
            ->filter(function (ModulAjarAbsensi $absensi) use ($awal, $akhir) {
                $pertemuan = $absensi->pertemuan;

                if (! $pertemuan || $pertemuan->selesai_pada === null || $pertemuan->tanggal === null) {
                    return false;
                }

                return $pertemuan->tanggal->betweenIncluded($awal, $akhir);
            })
            ->groupBy('siswa_id')
            ->map(fn (Collection $baris) => $baris->count());
    }

    private function status(int $kuota, int $hadir): string
    {
        if ($kuota === 0) {
            return 'tanpa_paket';
        }

        if ($hadir < $kuota) {
            return 'kurang';
        }

        return $hadir > $kuota ? 'lebih' : 'pas';
    }
}
