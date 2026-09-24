<?php

namespace App\Http\Controllers;

use App\Models\RaporCetak;
use App\Models\Siswa;
use App\Services\RaporService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RaporOrangTuaController extends Controller
{
    private const MAKS_PERCOBAAN = 8;

    private const JEDA_DETIK = 600;

    public function __construct(private readonly RaporService $rapor) {}

    public function form()
    {
        return view('publik.rapor', ['rapor' => null, 'siswa' => null, 'catatan' => null]);
    }

    public function cari(Request $request)
    {
        $kunci = 'rapor-ortu:'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            $menit = (int) ceil(RateLimiter::availableIn($kunci) / 60);

            return back()
                ->withInput($request->except('empat_digit'))
                ->withErrors(['nama' => "Terlalu banyak percobaan. Coba lagi sekitar {$menit} menit lagi."]);
        }

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'empat_digit' => ['required', 'string', 'regex:/^\d{4}$/'],
        ], [
            'nama.required' => 'Nama lengkap anak wajib diisi.',
            'empat_digit.required' => '4 angka terakhir nomor HP wajib diisi.',
            'empat_digit.regex' => 'Isi tepat 4 angka terakhir nomor HP yang terdaftar.',
        ]);

        RateLimiter::hit($kunci, self::JEDA_DETIK);

        $siswa = Siswa::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($data['nama']))])
            ->with('tingkatKemampuan:id,keterangan')
            ->first();

        $cocok = $siswa
            && filled($siswa->no_hp)
            && substr(preg_replace('/\D/', '', $siswa->no_hp), -4) === $data['empat_digit'];

        if (! $cocok) {
            return back()
                ->withInput($request->except('empat_digit'))
                ->withErrors(['nama' => 'Data tidak ditemukan. Periksa lagi ejaan nama lengkap anak dan 4 angka terakhir nomor HP yang terdaftar di E-Ling Course.']);
        }

        RateLimiter::clear($kunci);

        $rapor = $this->rapor->untukSiswa($siswa);
        $catatan = RaporCetak::terakhirUntuk($siswa->id);

        return view('publik.rapor', [
            'rapor' => $rapor,
            'siswa' => $siswa,
            'catatan' => $catatan && $catatan->adaCatatan() ? $catatan : null,
        ]);
    }
}
