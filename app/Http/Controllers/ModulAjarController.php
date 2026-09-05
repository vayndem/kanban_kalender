<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\Sesi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModulAjarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $guru = $user->isGuru() ? $user->guru : null;

        if ($user->isGuru() && ! $guru) {
            return view('guru.belum-tertaut', ['user' => $user]);
        }

        $query = Jadwal::query()
            ->with(['mataPelajaran:id,name', 'guru:id,name', 'ruang:id,name', 'siswa:id,name,panggilan'])
            ->select(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id', 'kode_kelas']);

        if ($guru) {
            // Selain kelas sendiri, guru juga perlu melihat kelas yang sedang dia
            // gantikan (guru_pengganti_id) supaya bisa masuk dan menilai pertemuan itu.
            $kodeKelasPengganti = ModulAjarDetail::where('guru_pengganti_id', $guru->id)
                ->with('modulAjar:id,kode_kelas')
                ->get()
                ->pluck('modulAjar.kode_kelas')
                ->filter();

            $query->where(function ($q) use ($guru, $kodeKelasPengganti) {
                $q->where('guru_id', $guru->id);
                if ($kodeKelasPengganti->isNotEmpty()) {
                    $q->orWhereIn('kode_kelas', $kodeKelasPengganti);
                }
            });
        }

        $jadwals = $query->get();

        $kelasList = $jadwals
            ->groupBy('kode_kelas')
            ->filter(fn ($rows, $kodeKelas) => filled($kodeKelas))
            ->map(function ($rows, $kodeKelas) {
                $first = $rows->first();

                return [
                    'kode_kelas' => $kodeKelas,
                    'hari_id' => $first->hari_id,
                    'sesi_id' => $first->sesi_id,
                    'mapel' => $first->mataPelajaran?->name ?? '-',
                    'guru' => $first->guru?->name ?? '-',
                    'guru_id' => $first->guru_id,
                    'ruang' => $first->ruang?->name ?? '-',
                    'jumlah_siswa' => $rows->count(),
                    'siswa_list' => $rows->pluck('siswa')->filter()->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'panggilan' => $s->panggilan,
                    ])->values(),
                ];
            })
            ->values();

        $modulAjars = ModulAjar::query()
            ->whereIn('kode_kelas', $kelasList->pluck('kode_kelas'))
            ->with([
                'details' => fn ($q) => $q->orderBy('id'),
                'details.guruPengganti:id,name',
                'details.diajarkanOlehGuru:id,name',
                'details.absensis.siswa:id,name,panggilan',
            ])
            ->get()
            ->keyBy('kode_kelas');

        $kelasList = $kelasList->map(function ($kelas) use ($modulAjars) {
            $modul = $modulAjars->get($kelas['kode_kelas']);
            $kelas['modul_ajar'] = $modul;
            $kelas['jumlah_detail'] = $modul ? $modul->details->count() : 0;
            $kelas['ada_header'] = (bool) $modul;

            return $kelas;
        })->values();

        $awalBulan = now()->startOfMonth()->toDateString();
        $akhirBulan = now()->endOfMonth()->toDateString();

        $absenBulanIni = null;
        $rekapAbsenGuru = null;

        if ($guru) {
            $absenBulanIni = AbsensiGuru::where('guru_id', $guru->id)
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->count();
        } else {
            $rekapAbsenGuru = AbsensiGuru::query()
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->with('guru:id,name')
                ->get()
                ->groupBy('guru_id')
                ->map(fn ($rows) => ['nama' => $rows->first()->guru?->name ?? '-', 'jumlah' => $rows->count()])
                ->sortByDesc('jumlah')
                ->values();
        }

        return view('modul-ajar.index', [
            'guru' => $guru,
            'isAdmin' => $user->isAdmin(),
            'haris' => Hari::orderBy('id')->get(['id', 'name']),
            'sesis' => Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']),
            'gurus' => Guru::orderBy('name')->get(['id', 'name']),
            'kelasList' => $kelasList,
            'absenBulanIni' => $absenBulanIni,
            'rekapAbsenGuru' => $rekapAbsenGuru,
        ]);
    }

    public function simpanHeader(Request $request, string $kodeKelas)
    {
        $jadwal = Jadwal::where('kode_kelas', $kodeKelas)->first();

        if (! $jadwal) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan.'], 404);
        }

        if (! $this->bolehKelola($jadwal)) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak mengisi modul ajar kelas ini.'], 403);
        }

        $modulAjarLama = ModulAjar::where('kode_kelas', $kodeKelas)->first();
        if ($modulAjarLama && ! Auth::user()->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Modul ajar ini sudah pernah diisi. Hanya admin yang bisa mengubahnya.'], 403);
        }

        $validated = $request->validate([
            'tujuan_pembelajaran' => 'required|string',
            'kompetensi_awal' => 'required|string',
            'model_pembelajaran' => 'required|string|max:255',
            'sarana_media' => 'required|string',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
        ]);

        $modulAjar = ModulAjar::updateOrCreate(['kode_kelas' => $kodeKelas], $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Modul ajar berhasil disimpan.',
            'data' => $modulAjar->load('details'),
        ]);
    }

    public function simpanDetail(Request $request, ModulAjar $modulAjar)
    {
        if (! $this->bolehKelolaKodeKelas($modulAjar->kode_kelas)) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak mengisi modul ajar kelas ini.'], 403);
        }

        $validated = $this->validasiDetail($request);
        $validated['modul_ajar_id'] = $modulAjar->id;
        $detail = ModulAjarDetail::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail modul ajar berhasil ditambahkan.',
            'data' => $detail,
        ]);
    }

    public function updateDetail(Request $request, ModulAjarDetail $detail)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya admin yang bisa mengubah detail modul ajar.'], 403);
        }

        $detail->update($this->validasiDetail($request));

        return response()->json([
            'status' => 'success',
            'message' => 'Detail modul ajar berhasil diperbarui.',
            'data' => $detail,
        ]);
    }

    public function hapusDetail(ModulAjarDetail $detail)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya admin yang bisa menghapus detail modul ajar.'], 403);
        }

        $detail->delete();

        return response()->json(['status' => 'success', 'message' => 'Detail modul ajar berhasil dihapus.']);
    }

    public function mulaiPersiapan(Request $request, ModulAjarDetail $detail)
    {
        if (! $this->bolehKelolaKodeKelas($detail->modulAjar->kode_kelas)) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak mempersiapkan pertemuan ini.'], 403);
        }

        $validated = $request->validate([
            'guru_pengganti_id' => 'nullable|integer|exists:gurus,id',
        ]);

        $detail->update([
            'sedang_dipersiapkan' => true,
            'guru_pengganti_id' => $validated['guru_pengganti_id'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Persiapan pertemuan dimulai.',
            'data' => $detail->fresh(['guruPengganti']),
        ]);
    }

    public function simpanNilai(Request $request, ModulAjarDetail $detail)
    {
        $kelas = Jadwal::where('kode_kelas', $detail->modulAjar->kode_kelas)->get(['id', 'siswa_id', 'guru_id']);

        if (! $this->bolehNilai($detail, $kelas->first())) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak menilai pertemuan ini.'], 403);
        }

        $rosterIds = $kelas->pluck('siswa_id');

        $validated = $request->validate([
            'absensi' => 'required|array|min:1',
            'absensi.*.siswa_id' => ['required', 'integer', Rule::in($rosterIds)],
            'absensi.*.hadir' => 'required|boolean',
            'absensi.*.nilai' => 'nullable|integer|min:1|max:5',
        ], [
            'absensi.*.siswa_id.in' => 'Ada siswa yang bukan bagian dari kelas ini.',
        ]);

        foreach ($validated['absensi'] as $item) {
            if ($item['hadir'] && blank($item['nilai'] ?? null)) {
                return response()->json(['status' => 'error', 'message' => 'Nilai wajib diisi untuk siswa yang hadir.'], 422);
            }
        }

        $guruKredit = $detail->guru_pengganti_id ?: $kelas->first()?->guru_id;

        DB::transaction(function () use ($detail, $validated, $guruKredit) {
            $now = now();
            ModulAjarAbsensi::upsert(
                collect($validated['absensi'])->map(fn ($item) => [
                    'modul_ajar_detail_id' => $detail->id,
                    'siswa_id' => $item['siswa_id'],
                    'hadir' => $item['hadir'],
                    'nilai' => $item['hadir'] ? $item['nilai'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
                ['modul_ajar_detail_id', 'siswa_id'],
                ['hadir', 'nilai', 'updated_at']
            );

            AbsensiGuru::updateOrCreate(
                ['modul_ajar_detail_id' => $detail->id],
                ['guru_id' => $guruKredit, 'tanggal' => now()->toDateString()]
            );

            $detail->update([
                'sedang_dipersiapkan' => false,
                'guru_pengganti_id' => null,
                'diajarkan_oleh_guru_id' => $guruKredit,
                'tanggal_diajarkan' => now()->toDateString(),
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai berhasil disimpan.',
            'data' => $detail->fresh(['absensis.siswa:id,name,panggilan', 'diajarkanOlehGuru:id,name']),
        ]);
    }

    private function validasiDetail(Request $request): array
    {
        return $request->validate([
            'materi' => 'required|string|max:255',
            'sub_materi' => 'nullable|string|max:255',
            'cara_mengajar' => 'nullable|string',
            'tugas' => 'nullable|string',
            'tujuan' => 'nullable|string',
            'hasil_akhir_pembelajaran' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ], [
            'materi.required' => 'Materi wajib diisi.',
        ]);
    }

    private function bolehKelola(Jadwal $jadwal): bool
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru() && $user->guru && (int) $jadwal->guru_id === (int) $user->guru->id;
    }

    private function bolehKelolaKodeKelas(string $kodeKelas): bool
    {
        $jadwal = Jadwal::where('kode_kelas', $kodeKelas)->first();

        return $jadwal && $this->bolehKelola($jadwal);
    }

    private function bolehNilai(ModulAjarDetail $detail, ?Jadwal $jadwalKelas = null): bool
    {
        $jadwalKelas ??= Jadwal::where('kode_kelas', $detail->modulAjar->kode_kelas)->first();

        if ($jadwalKelas && $this->bolehKelola($jadwalKelas)) {
            return true;
        }

        $user = Auth::user();

        return $user->isGuru() && $user->guru && (int) $detail->guru_pengganti_id === (int) $user->guru->id;
    }
}
