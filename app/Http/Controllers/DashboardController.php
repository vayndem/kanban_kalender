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

class DashboardController extends Controller
{
    public function index()
    {
        $haris = Hari::orderBy('id')->get();
        $sesis = Sesi::orderBy('id')->get();

        $allGurus = Guru::orderBy('name')->get();
        $allMapels = MataPelajaran::orderBy('name')->get();
        $allRuangs = Ruang::orderBy('name')->get();
        $allSiswas = Siswa::with('tandas')->orderBy('name')->get();
        $jadwalsData = Jadwal::with([
            'siswa.tandas',
            'mataPelajaran',
            'guru',
            'ruang'
        ])
        ->get();

        $finalJadwals = [];
        foreach ($jadwalsData as $jadwal) {
            $classKey = $jadwal->mata_pelajaran_id . '_' . $jadwal->guru_id . '_' . $jadwal->ruang_id;

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

        return view('admin.dashboard', [
            'haris' => $haris,
            'sesis' => $sesis,
            'jadwals' => $finalJadwals,
            'allGurus' => $allGurus,
            'allMapels' => $allMapels,
            'allRuangs' => $allRuangs,
            'allSiswas' => $allSiswas,
        ]);
    }
}
