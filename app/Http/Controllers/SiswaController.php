<?php

namespace App\Http\Controllers;

use App\Exports\SiswaExport;
use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaMassalImport;
use App\Models\Arsip;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Paket;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Support\NomorHp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    private static function aturanNoHp(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if (filled($value) && NomorHp::normalkan($value) === null) {
                $fail('Nomor WhatsApp tidak dikenali formatnya. Contoh: 08xxxxxxxxxx atau +628xxxxxxxxxx.');
            }
        };
    }

    public function jadwal(Siswa $siswa): JsonResponse
    {
        $jadwals = Jadwal::query()
            ->select(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
            ->where('siswa_id', $siswa->id)
            ->with([
                'mataPelajaran:id,name',
                'guru:id,name',
                'ruang:id,name',
                'hari:id,name',
                'sesi:id,name,start_time,end_time',
            ])
            ->orderBy('hari_id')
            ->orderBy('sesi_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $jadwals,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:siswas,name',
            'panggilan' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => ['nullable', 'string', 'max:20', self::aturanNoHp()],
            'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
            'tingkat_kemampuan_id' => 'nullable|integer|exists:tingkat_kemampuans,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama siswa sudah terdaftar di sistem.',
            'paket_pembayaran.exists' => 'Paket pembayaran yang dipilih tidak valid.',
            'tingkat_kemampuan_id.exists' => 'Tingkat kemampuan yang dipilih tidak valid.',
        ]);

        try {
            $siswa = Siswa::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil ditambahkan.',
                    'data' => $siswa,
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil ditambahkan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menyimpan', $e);
        }
    }

    public function update(Request $request, $id)
    {
        $siswa = Siswa::find($id);

        if (! $siswa) {
            return $this->handleNotFound($request, "Siswa (ID: $id)");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:siswas,name,'.$id,
            'panggilan' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => ['nullable', 'string', 'max:20', self::aturanNoHp()],
            'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
            'tingkat_kemampuan_id' => 'nullable|integer|exists:tingkat_kemampuans,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama siswa sudah digunakan oleh data lain.',
            'paket_pembayaran.exists' => 'Paket pembayaran tidak ditemukan.',
            'tingkat_kemampuan_id.exists' => 'Tingkat kemampuan yang dipilih tidak valid.',
        ]);

        try {
            $siswa->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil diperbarui.',
                    'data' => $siswa,
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memperbarui', $e);
        }
    }

    public function downloadImportTemplate()
    {
        return Excel::download(new SiswaTemplateExport, 'Kerangka-Import-Siswa.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ], [
            'file.required' => 'Pilih file kerangka yang sudah diisi.',
            'file.mimes' => 'File harus berformat Excel (.xlsx/.xls) atau CSV.',
        ]);

        try {
            $import = new SiswaMassalImport;
            Excel::import($import, $request->file('file'));

            $pesan = "Impor selesai: {$import->dibuat} siswa baru, {$import->diperbarui} siswa diperbarui";
            if ($import->dilewati > 0) {
                $pesan .= ", {$import->dilewati} baris dilewati (nama kosong)";
            }
            $pesan .= '.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $pesan]);
            }

            return redirect()->back()->with('success', $pesan);
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal mengimpor', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $ids = collect(explode(',', (string) $id))
                ->filter(fn ($studentId) => ctype_digit(trim($studentId)))
                ->map(fn ($studentId) => (int) $studentId)
                ->unique()
                ->values();
            $siswas = Siswa::query()->whereKey($ids)->get();

            if ($siswas->isEmpty()) {
                return $this->handleNotFound($request, 'Siswa');
            }

            DB::transaction(function () use ($siswas) {
                $now = now();
                $archiveRows = $siswas
                    ->map(fn (Siswa $siswa) => [
                        'name' => $siswa->name,
                        'panggilan' => $siswa->panggilan,
                        'kelas' => $siswa->kelas,
                        'no_hp' => $siswa->no_hp,
                        'paket_pembayaran' => $siswa->paket_pembayaran,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();
                $studentIds = $siswas->pluck('id');

                Arsip::query()->insert($archiveRows);
                Jadwal::query()->whereIn('siswa_id', $studentIds)->delete();
                DB::table('tandas')->whereIn('siswa_id', $studentIds)->delete();
                Siswa::query()->whereIn('id', $studentIds)->delete();
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $siswas->count().' siswa berhasil diarsipkan dan jadwal telah dibersihkan.',
                ]);
            }

            return redirect()->back()->with('success', $siswas->count().' siswa berhasil diarsipkan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses', $e);
        }
    }

    private function handleNotFound($request, $item)
    {
        $msg = "Maaf, data $item tidak ditemukan. Silakan segarkan halaman.";

        return $request->wantsJson()
            ? response()->json(['status' => 'error', 'message' => $msg], 404)
            : redirect()->back()->with('error', $msg);
    }

    private function handleException($request, $prefix, $e)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $prefix.': '.$e->getMessage(),
            ], 500);
        }

        return redirect()->back()->withInput()->with('error', $prefix.': '.$e->getMessage());
    }

    public function exportExcel(Request $request)
    {
        $query = Siswa::with([
            'paket',
            'jadwals.mataPelajaran',
            'jadwals.guru',
            'jadwals.ruang',
            'jadwals.hari',
            'jadwals.sesi',
        ])->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('kelas', 'like', "%$search%");
            });
        }

        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        if ($request->filled('paket_id')) {
            $query->where('paket_pembayaran', $request->paket_id);
        }

        if ($request->filled('sesi_ids')) {
            $sesiIds = array_filter(explode(',', $request->sesi_ids));
            $query->whereHas('jadwals', function ($q) use ($sesiIds) {
                $q->whereIn('sesi_id', $sesiIds);
            });
        }

        if ($request->filled('guru_ids')) {
            $guruIds = array_filter(explode(',', $request->guru_ids));
            $query->whereHas('jadwals', function ($q) use ($guruIds) {
                $q->whereIn('guru_id', $guruIds);
            });
        }

        if ($request->filled('ruang_ids')) {
            $ruangIds = array_filter(explode(',', $request->ruang_ids));
            $query->whereHas('jadwals', function ($q) use ($ruangIds) {
                $q->whereIn('ruang_id', $ruangIds);
            });
        }

        $siswas = $query->get();
        $filterLabel = $this->buildFilterLabel($request);

        return Excel::download(new SiswaExport($siswas, $filterLabel), 'Data-Siswa-'.now()->format('YmdHis').'.xlsx');
    }

    private function buildFilterLabel(Request $request): string
    {
        $parts = [];

        if ($request->filled('kelas')) {
            $parts[] = 'Kelas: '.$request->kelas;
        }

        if ($request->filled('paket_id')) {
            $paket = Paket::find($request->paket_id);
            $parts[] = 'Paket: '.($paket ? $paket->nama_paket : $request->paket_id);
        }

        if ($request->filled('sesi_ids')) {
            $ids = array_filter(explode(',', $request->sesi_ids));
            $names = Sesi::whereIn('id', $ids)->pluck('name')->join(', ');
            $parts[] = 'Sesi: '.$names;
        }

        if ($request->filled('guru_ids')) {
            $ids = array_filter(explode(',', $request->guru_ids));
            $names = Guru::whereIn('id', $ids)->pluck('name')->join(', ');
            $parts[] = 'Guru: '.$names;
        }

        if ($request->filled('ruang_ids')) {
            $ids = array_filter(explode(',', $request->ruang_ids));
            $names = Ruang::whereIn('id', $ids)->pluck('name')->join(', ');
            $parts[] = 'Ruang: '.$names;
        }

        if ($request->filled('search')) {
            $parts[] = 'Cari: "'.$request->search.'"';
        }

        return $parts ? implode(' | ', $parts) : 'Semua Siswa';
    }
}
