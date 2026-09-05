<?php

namespace App\Http\Controllers;

use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\Sesi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuruPortalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (! $guru) {
            return view('guru.belum-tertaut', ['user' => $user]);
        }

        $haris = Hari::orderBy('id')->get(['id', 'name']);
        $sesis = Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']);

        $jadwals = Jadwal::query()
            ->where('guru_id', $guru->id)
            ->with([
                'siswa:id,name,panggilan,kelas',
                'mataPelajaran:id,name',
                'ruang:id,name',
            ])
            ->get(['id', 'siswa_id', 'mata_pelajaran_id', 'guru_id', 'hari_id', 'ruang_id', 'sesi_id']);

        $kelas = $jadwals
            ->groupBy(fn(Jadwal $j) => implode('-', [
                $j->hari_id,
                $j->sesi_id,
                $j->mata_pelajaran_id,
                $j->ruang_id,
            ]))
            ->map(fn($rows) => [
                'hari_id' => $rows->first()->hari_id,
                'sesi_id' => $rows->first()->sesi_id,
                'mata_pelajaran' => $rows->first()->mataPelajaran?->name ?? '-',
                'ruang' => $rows->first()->ruang?->name ?? '-',
                'siswa' => $rows->map(fn($r) => [
                    'nama' => $r->siswa?->name ?? '-',
                    'panggilan' => $r->siswa?->panggilan,
                    'kelas' => $r->siswa?->kelas,
                ])->sortBy('nama')->values(),
            ])
            ->values();

        return view('guru.jadwal', [
            'guru' => $guru,
            'haris' => $haris,
            'sesis' => $sesis,
            'kelas' => $kelas,
            'totalKelas' => $kelas->count(),
            'totalSiswa' => $jadwals->pluck('siswa_id')->unique()->count(),
        ]);
    }
}
