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
use App\Models\Pembayaran;
use App\Models\Paket;
use App\Models\Arsip;

class DashboardController extends Controller
{
    public function index()
    {
        $haris = Hari::orderBy('id')->get();
        $sesis = Sesi::orderBy('start_time')->get();
        $allGurus = Guru::orderBy('name')->get();
        $allMapels = MataPelajaran::orderBy('name')->get();
        $allRuangs = Ruang::orderBy('name')->get();
        $allSiswas = Siswa::with('tandas')->orderBy('name')->get();
        $allArsips = Arsip::orderBy('name')->get();
        $pakets = Paket::orderBy('nama_paket')->get();

        $jadwalsWithRelations = Jadwal::with(['siswa.tandas', 'mataPelajaran', 'guru', 'ruang'])->get();

        $jadwalsData = $jadwalsWithRelations;

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

        $pembayaranSummaries = Pembayaran::with(['siswa.tandas'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'id_siswa' => $item->id_siswa,
                    'siswa' => $item->siswa,
                    'harga' => (int) $item->harga,
                    'status' => (int) $item->status,
                    'bulan' => $item->created_at->format('m'),
                    'tanggal_format' => $item->created_at->translatedFormat('d F Y'),
                ];
            });

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
        ]);
    }

    public function guestIndex()
    {
        $dayOfWeek = \Carbon\Carbon::now()->isoFormat('E');

        $jadwalHariIni = Jadwal::with(['mataPelajaran', 'guru', 'ruang', 'sesi'])
            ->where('hari_id', $dayOfWeek)
            ->get();

        $stats = [
            'total_siswa' => Siswa::count(),
            'kelas_aktif' => $jadwalHariIni->groupBy(function ($q) {
                return $q->mata_pelajaran_id . $q->guru_id . $q->sesi_id;
            })->count(),
            'pengajar' => Guru::count(),
        ];

        $listJadwal = $jadwalHariIni->unique(function ($item) {
            return $item->sesi_id . $item->mata_pelajaran_id . $item->guru_id;
        })->groupBy(function ($item) {
            return $item->sesi->name;
        });

        return view('welcome', compact('stats', 'listJadwal'));
    }
}
