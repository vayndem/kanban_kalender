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
use App\Models\TingkatKemampuan;
use App\Support\NomorHp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    private const KOLOM_PAKET = [
        'paket_pembayaran',
        'paket_pembayaran_2',
        'paket_pembayaran_3',
        'paket_pembayaran_4',
        'paket_pembayaran_5',
    ];

    private const FILTER_JADWAL = [
        'sesi_ids' => 'sesi_id',
        'guru_ids' => 'guru_id',
        'ruang_ids' => 'ruang_id',
    ];

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
            'paket_pembayaran_2' => 'nullable|integer|exists:pakets,id',
            'paket_pembayaran_3' => 'nullable|integer|exists:pakets,id',
            'paket_pembayaran_4' => 'nullable|integer|exists:pakets,id',
            'paket_pembayaran_5' => 'nullable|integer|exists:pakets,id',
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
            'paket_pembayaran_2' => 'nullable|integer|exists:pakets,id',
            'paket_pembayaran_3' => 'nullable|integer|exists:pakets,id',
            'paket_pembayaran_4' => 'nullable|integer|exists:pakets,id',
            'paket_pembayaran_5' => 'nullable|integer|exists:pakets,id',
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
                        'paket_pembayaran_2' => $siswa->paket_pembayaran_2,
                        'paket_pembayaran_3' => $siswa->paket_pembayaran_3,
                        'paket_pembayaran_4' => $siswa->paket_pembayaran_4,
                        'paket_pembayaran_5' => $siswa->paket_pembayaran_5,
                        'tingkat_kemampuan_id' => $siswa->tingkat_kemampuan_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();
                $studentIds = $siswas->pluck('id');

                Arsip::query()->insert($archiveRows);
                $this->arsipkanRiwayatUang($studentIds, $now);
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

    /**
     * @param  Collection<int, int>  $studentIds
     */
    private function arsipkanRiwayatUang($studentIds, $now): void
    {
        $tagihan = DB::table('pembayarans')->whereIn('id_siswa', $studentIds)->get();

        if ($tagihan->isEmpty()) {
            return;
        }

        $detail = DB::table('pembayaran_details')
            ->whereIn('id_pembayaran', $tagihan->pluck('id'))
            ->get()
            ->groupBy('id_pembayaran');

        $baris = $tagihan->map(fn ($t) => [
            'kelompok' => 'arsip-siswa',
            'id_pembayaran_induk' => $t->id,
            'id_pembayaran_dibuang' => $t->id,
            'nilai_tagihan_dibuang' => (int) $t->harga,
            'nilai_detail_dibuang' => (int) ($detail[$t->id] ?? collect())->sum('pembayaran'),
            'alasan' => 'Siswa diarsipkan, tagihan dan riwayat pembayarannya ikut terhapus',
            'data_asli' => json_encode([
                'pembayaran' => $t,
                'details' => ($detail[$t->id] ?? collect())->values(),
            ]),
            'user_id' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($baris, 200) as $bagian) {
            DB::table('koreksi_pembayaran_logs')->insert($bagian);
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

        $kelas = $this->daftarFilter($request, 'kelas');
        if ($kelas !== []) {
            $query->whereIn('kelas', $kelas);
        }

        $paketIds = $this->daftarFilter($request, 'paket_ids', 'paket_id');
        if ($paketIds !== []) {
            $query->where(function ($q) use ($paketIds) {
                foreach (self::KOLOM_PAKET as $kolom) {
                    $q->orWhereIn($kolom, $paketIds);
                }
            });
        }

        $kemampuanIds = $this->daftarFilter($request, 'kemampuan_ids', 'kemampuan_id');
        if ($kemampuanIds !== []) {
            $query->whereIn('tingkat_kemampuan_id', $kemampuanIds);
        }

        foreach (self::FILTER_JADWAL as $parameter => $kolom) {
            $ids = $this->daftarFilter($request, $parameter);
            if ($ids !== []) {
                $query->whereHas('jadwals', fn ($q) => $q->whereIn($kolom, $ids));
            }
        }

        $siswas = $query->get();
        $filterLabel = $this->buildFilterLabel($request);

        return Excel::download(new SiswaExport($siswas, $filterLabel), 'Data-Siswa-'.now()->format('YmdHis').'.xlsx');
    }

    /**
     * @return array<int, string>
     */
    private function daftarFilter(Request $request, string ...$kunci): array
    {
        foreach ($kunci as $nama) {
            $nilai = $request->input($nama);

            if (is_string($nilai)) {
                $nilai = explode(',', $nilai);
            }

            if (! is_array($nilai)) {
                $nilai = $nilai === null ? [] : [$nilai];
            }

            $bersih = array_values(array_filter(
                array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $nilai),
                fn ($item) => $item !== ''
            ));

            if ($bersih !== []) {
                return $bersih;
            }
        }

        return [];
    }

    private function buildFilterLabel(Request $request): string
    {
        $parts = [];

        $kelas = $this->daftarFilter($request, 'kelas');
        if ($kelas !== []) {
            $parts[] = 'Kelas: '.implode(', ', $kelas);
        }

        $paketIds = $this->daftarFilter($request, 'paket_ids', 'paket_id');
        if ($paketIds !== []) {
            $parts[] = 'Paket: '.$this->namaAtauId(Paket::whereIn('id', $paketIds)->pluck('nama_paket', 'id'), $paketIds);
        }

        $kemampuanIds = $this->daftarFilter($request, 'kemampuan_ids', 'kemampuan_id');
        if ($kemampuanIds !== []) {
            $nama = TingkatKemampuan::whereIn('id', $kemampuanIds)
                ->get()
                ->mapWithKeys(fn ($item) => [$item->id => 'Level '.$item->level]);
            $parts[] = 'Kemampuan: '.$this->namaAtauId($nama, $kemampuanIds);
        }

        $sesiIds = $this->daftarFilter($request, 'sesi_ids');
        if ($sesiIds !== []) {
            $parts[] = 'Sesi: '.$this->namaAtauId(Sesi::whereIn('id', $sesiIds)->pluck('name', 'id'), $sesiIds);
        }

        $guruIds = $this->daftarFilter($request, 'guru_ids');
        if ($guruIds !== []) {
            $parts[] = 'Guru: '.$this->namaAtauId(Guru::whereIn('id', $guruIds)->pluck('name', 'id'), $guruIds);
        }

        $ruangIds = $this->daftarFilter($request, 'ruang_ids');
        if ($ruangIds !== []) {
            $parts[] = 'Ruang: '.$this->namaAtauId(Ruang::whereIn('id', $ruangIds)->pluck('name', 'id'), $ruangIds);
        }

        if ($request->filled('search')) {
            $parts[] = 'Cari: "'.$request->search.'"';
        }

        return $parts ? implode(' | ', $parts) : 'Semua Siswa';
    }

    /**
     * @param  Collection<int|string, string>  $nama
     * @param  array<int, string>  $ids
     */
    private function namaAtauId($nama, array $ids): string
    {
        return collect($ids)
            ->map(fn ($id) => $nama[$id] ?? $nama[(int) $id] ?? $id)
            ->implode(', ');
    }
}
