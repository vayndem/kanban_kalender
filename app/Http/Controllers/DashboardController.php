<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jadwal;
use App\Models\Hari;
use App\Models\Sesi;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Siswa;
use App\Models\Diskon;
use App\Models\Pembayaran;
use App\Models\Paket;
use App\Models\Arsip;

class DashboardController extends Controller
{
    public function index()
    {
        $activeTab = request()->string('tab')->toString();
        if (!in_array($activeTab, ['jadwal', 'data_siswa', 'pembayaran'], true)) {
            $activeTab = 'jadwal';
        }

        $haris = Hari::orderBy('id')->get();
        $sesis = Sesi::orderBy('start_time')->get();
        $allGurus = Guru::orderBy('name')->get();
        $allMapels = MataPelajaran::orderBy('name')->get();
        $allRuangs = Ruang::orderBy('name')->get();
        $allSiswas = collect();
        $allArsips = collect();
        $pakets = collect();
        $diskons = collect();
        $jadwalsData = collect();
        $jadwalsWithRelations = collect();
        $scheduleSearchIndex = ['days' => [], 'sessions' => []];
        $scheduleOccupancy = collect();

        if ($activeTab === 'jadwal') {
            $allSiswas = Siswa::select(['id', 'name', 'panggilan', 'kelas', 'no_hp'])->with('tandas:id,siswa_id,keterangan,created_at')->orderBy('name')->get();
            $jadwalsWithRelations = Jadwal::select(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
                ->with(['siswa:id,name,panggilan,kelas', 'siswa.tandas:id,siswa_id,keterangan,created_at', 'mataPelajaran:id,name', 'guru:id,name', 'ruang:id,name', 'hari:id,name', 'sesi:id,name,start_time,end_time'])
                ->get();
            $jadwalsData = $jadwalsWithRelations
                ->map(fn (Jadwal $jadwal) => ['siswa_id' => $jadwal->siswa_id])
                ->unique('siswa_id')
                ->values();
            $scheduleOccupancy = $jadwalsWithRelations->map(fn (Jadwal $jadwal) => [
                'hari_id' => $jadwal->hari_id,
                'sesi_id' => $jadwal->sesi_id,
                'mapel_id' => $jadwal->mata_pelajaran_id,
                'guru_id' => $jadwal->guru_id,
                'ruang_id' => $jadwal->ruang_id,
                'siswa_id' => $jadwal->siswa_id,
            ])->values();

            foreach ($jadwalsWithRelations as $jadwal) {
                $searchText = mb_strtolower(implode(' ', array_filter([
                    $jadwal->hari?->name,
                    $jadwal->sesi?->name,
                    $jadwal->mataPelajaran?->name,
                    $jadwal->guru?->name,
                    $jadwal->ruang?->name,
                    $jadwal->siswa?->name,
                    $jadwal->siswa?->panggilan,
                    $jadwal->siswa?->kelas,
                ])));
                $scheduleSearchIndex['days'][$jadwal->hari_id] = trim(($scheduleSearchIndex['days'][$jadwal->hari_id] ?? '') . ' ' . $searchText);
                $scheduleSearchIndex['sessions'][$jadwal->sesi_id] = trim(($scheduleSearchIndex['sessions'][$jadwal->sesi_id] ?? '') . ' ' . $searchText);
            }
        } elseif ($activeTab === 'data_siswa') {
            $allSiswas = Siswa::with('tandas:id,siswa_id,keterangan,created_at')->orderBy('name')->get();
            $allArsips = Arsip::orderBy('name')->get();
            $pakets = Paket::orderBy('nama_paket')->get();
            $jadwalsData = Jadwal::select(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])->get();
        } else {
            $allSiswas = Siswa::select(['id', 'name', 'panggilan', 'kelas', 'no_hp', 'paket_pembayaran', 'paket_pembayaran_2', 'paket_pembayaran_3', 'paket_pembayaran_4', 'paket_pembayaran_5'])->orderBy('name')->get();
            $pakets = Paket::orderBy('nama_paket')->get();
            $diskons = Diskon::orderBy('id', 'desc')->get();
        }

        $finalJadwals = [];
        foreach ($jadwalsWithRelations as $jadwal) {
            $classKey = "{$jadwal->mata_pelajaran_id}_{$jadwal->guru_id}_{$jadwal->ruang_id}";

            if (!isset($finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey])) {
                $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey] = [
                    'mapel' => $jadwal->mataPelajaran,
                    'guru'  => $jadwal->guru,
                    'ruang' => $jadwal->ruang,
                    'siswa_list' => collect()
                ];
            }
            $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey]['siswa_list']->push($jadwal->siswa);
        }

        $pembayaranSummaries = $activeTab === 'pembayaran' ? Pembayaran::select([
                'id',
                'id_siswa',
                'harga',
                'status',
                'keterangan',
                'tanggal_pembayaran',
                'pembayaran_via',
                'no_hp',
                'total_sudah_dibayar',
                'created_at',
            ])->with(['siswa:id,name,panggilan,kelas,no_hp'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $namaPaket = '-';
                if (preg_match('/Pembayaran Paket (.*?) \(/', $item->keterangan, $matches)) {
                    $namaPaket = $matches[1];
                } elseif (preg_match('/Pembayaran Paket (.*)/', $item->keterangan, $matches)) {
                    $namaPaket = $matches[1];
                }

                return [
                    'id' => $item->id,
                    'id_siswa' => $item->id_siswa,
                    'siswa' => $item->siswa,
                    'harga' => (int) $item->harga,
                    'status' => (int) $item->status,
                    'keterangan' => $item->keterangan,
                    'nama_paket' => $namaPaket,
                    'tanggal_pembayaran' => $item->tanggal_pembayaran ? \Carbon\Carbon::parse($item->tanggal_pembayaran)->translatedFormat('d F Y') : '-',
                    'pembayaran_via' => $item->pembayaran_via,
                    'no_hp' => $item->no_hp,
                    'total_sudah_dibayar' => (int) $item->total_sudah_dibayar,
                    'bulan' => $item->created_at->format('m'),
                    'tanggal_format' => $item->created_at->translatedFormat('d F Y'),
                ];
            }) : collect();

        return view('admin.dashboard', [
            'haris' => $haris,
            'sesis' => $sesis,
            'jadwals' => $finalJadwals,
            'allGurus' => $allGurus,
            'allMapels' => $allMapels,
            'allRuangs' => $allRuangs,
            'allSiswas' => $allSiswas,
            'allArsips' => $allArsips,
            'pembayaranSummaries' => $pembayaranSummaries,
            'pakets' => $pakets,
            'jadwalsData' => $jadwalsData,
            'diskons' => $diskons,
            'activeTab' => $activeTab,
            'scheduleSearchIndex' => $scheduleSearchIndex,
            'scheduleOccupancy' => $scheduleOccupancy,
        ]);
    }

    public function guestIndex()
    {
        $dayOfWeek = \Carbon\Carbon::now()->isoFormat('E');

        $jadwalHariIni = Jadwal::with([
            'mataPelajaran:id,name',
            'guru:id,name',
            'ruang:id,name',
            'sesi:id,name,start_time,end_time',
            'siswa:id,name,kelas',
        ])
            ->where('hari_id', $dayOfWeek)
            ->get();

        $stats = [
            'total_siswa' => Siswa::count(),
            'kelas_aktif' => $jadwalHariIni->groupBy(function ($q) {
                return $q->mata_pelajaran_id . $q->guru_id . $q->sesi_id;
            })->count(),
            'pengajar' => Guru::count(),
        ];

        $kelasHariIni = $jadwalHariIni->groupBy(function ($item) {
            return implode('_', [$item->hari_id, $item->sesi_id, $item->mata_pelajaran_id, $item->guru_id, $item->ruang_id]);
        })->map(function ($items) {
            $kelas = $items->first();
            $kelas->slot_students = $items
                ->filter(fn ($jadwal) => $jadwal->siswa !== null)
                ->map(fn ($jadwal) => [
                    'name' => $jadwal->siswa->name,
                    'kelas' => $jadwal->siswa->kelas ?? 'N/A',
                ])
                ->values();

            return $kelas;
        })->values();

        $listJadwal = $kelasHariIni
            ->sortBy(function ($item) {
                return $item->sesi->start_time;
            })
            ->groupBy(function ($item) {
                return $item->sesi->name;
            });

        return view('welcome', compact('stats', 'listJadwal'));
    }
}
