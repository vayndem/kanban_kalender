<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\TingkatKemampuan;
use App\Services\IrisanSesiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkshopController extends Controller
{
    public function index(Request $request)
    {
        $jadwals = Jadwal::query()
            ->select(['id', 'siswa_id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id'])
            ->with([
                'siswa:id,kelas',
                'hari:id,name',
                'sesi:id,name,start_time,end_time',
                'mataPelajaran:id,name',
                'guru:id,name',
                'ruang:id,name',
            ])
            ->get();

        $siswas = Siswa::orderBy('name')->get([
            'id',
            'name',
            'panggilan',
            'kelas',
            'no_hp',
            'paket_pembayaran',
            'paket_pembayaran_2',
            'paket_pembayaran_3',
            'paket_pembayaran_4',
            'paket_pembayaran_5',
            'tingkat_kemampuan_id',
        ]);

        return view('admin.workshop', [
            'mapels' => $this->entitasDenganKonteks(MataPelajaran::orderBy('name')->get(['id', 'name']), $jadwals, 'mata_pelajaran_id'),
            'gurus' => $this->entitasDenganKonteks(Guru::orderBy('name')->get(['id', 'name', 'email']), $jadwals, 'guru_id'),
            'ruangs' => $this->entitasDenganKonteks(Ruang::orderBy('name')->get(['id', 'name']), $jadwals, 'ruang_id'),
            'sesis' => $this->entitasDenganKonteks(Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']), $jadwals, 'sesi_id'),
            'pakets' => $this->paketsDenganKonteks($siswas),
            'kemampuans' => $this->kemampuanDenganKonteks($siswas),
            'siswas' => $siswas,
            'ketersediaan' => $this->petaKetersediaan($jadwals),
            'petaKelas' => $this->petaKelas($jadwals),
            'ringkasan' => [
                'guru' => Guru::count(),
                'ruang' => Ruang::count(),
                'sesi' => Sesi::count(),
                'mapel' => MataPelajaran::count(),
                'siswa' => Siswa::count(),
                'paket' => Paket::count(),
            ],
            'editSiswaId' => $request->integer('edit_siswa') ?: null,
        ]);
    }

    private function entitasDenganKonteks(Collection $items, Collection $jadwals, string $kolom, string $labelJumlah = 'jumlah_baris_jadwal'): Collection
    {
        $terpakai = $jadwals->countBy($kolom);

        return $items->map(fn ($item) => array_merge($item->toArray(), [
            $labelJumlah => (int) ($terpakai[$item->id] ?? 0),
            'bisa_dihapus' => (int) ($terpakai[$item->id] ?? 0) === 0,
        ]));
    }

    private function paketsDenganKonteks(Collection $siswas): Collection
    {
        $terpakai = [];
        foreach ($siswas as $siswa) {
            foreach (PaketController::KOLOM_PAKET_SISWA as $kolom) {
                if ($siswa->{$kolom}) {
                    $terpakai[$siswa->{$kolom}] = ($terpakai[$siswa->{$kolom}] ?? 0) + 1;
                }
            }
        }

        return Paket::orderBy('nama_paket')->get(['id', 'nama_paket', 'harga', 'pertemuan'])
            ->map(fn (Paket $p) => array_merge($p->toArray(), ['jumlah_siswa' => $terpakai[$p->id] ?? 0]));
    }

    private function kemampuanDenganKonteks(Collection $siswas): Collection
    {
        $semua = TingkatKemampuan::orderBy('level')->get(['id', 'level', 'keterangan']);
        $levelTertinggi = $semua->max('level');

        return $this->entitasDenganKonteks($semua, $siswas, 'tingkat_kemampuan_id', 'jumlah_siswa')
            ->map(fn (array $k) => array_merge($k, [
                'bisa_dihapus' => $k['bisa_dihapus'] && $k['level'] === $levelTertinggi,
            ]));
    }

    private function petaKetersediaan(Collection $jadwals): array
    {
        $haris = Hari::orderBy('id')->get(['id', 'name']);
        $sesis = Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']);
        $ruangs = Ruang::orderBy('name')->get(['id', 'name']);
        $gurus = Guru::orderBy('name')->get(['id', 'name']);

        $peta = [];

        $irisan = app(IrisanSesiService::class);

        foreach ($haris as $hari) {
            foreach ($sesis as $sesi) {
                $diHari = $jadwals->where('hari_id', $hari->id);
                $diSlot = $diHari->where('sesi_id', $sesi->id);

                $beririsan = $irisan->idBeririsan($sesi->id);
                $diWaktuIni = $diHari->whereIn('sesi_id', $beririsan);

                $ruangTerpakai = $diWaktuIni->pluck('ruang_id')->unique();
                $guruTerpakai = $diWaktuIni->pluck('guru_id')->unique();

                $peta[] = [
                    'hari' => $hari->name,
                    'sesi' => $sesi->name.' - '.Carbon::parse($sesi->start_time)->format('H:i').'–'.Carbon::parse($sesi->end_time)->format('H:i'),
                    'kelas_berjalan' => $diSlot->unique(fn ($j) => "{$j->ruang_id}_{$j->guru_id}")->count(),
                    'ruang_kosong' => $ruangs->whereNotIn('id', $ruangTerpakai)->pluck('name')->values(),
                    'guru_kosong' => $gurus->whereNotIn('id', $guruTerpakai)->pluck('name')->values(),
                ];
            }
        }

        return $peta;
    }

    private function petaKelas(Collection $jadwals): array
    {
        $peta = [];

        foreach ($jadwals as $jadwal) {
            $kelas = $jadwal->siswa?->kelas;
            if (blank($kelas)) {
                continue;
            }

            $kunci = implode('_', [
                $jadwal->hari_id,
                $jadwal->sesi_id,
                $jadwal->mata_pelajaran_id,
                $jadwal->guru_id,
                $jadwal->ruang_id,
            ]);

            $peta[$kelas][$kunci] = [
                'hari' => $jadwal->hari?->name,
                'sesi' => $jadwal->sesi?->name,
                'mapel' => $jadwal->mataPelajaran?->name,
                'guru' => $jadwal->guru?->name,
                'ruang' => $jadwal->ruang?->name,
            ];
        }

        return collect($peta)->map(fn ($rows) => array_values($rows))->all();
    }
}
