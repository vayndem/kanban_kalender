<?php

namespace App\Http\Controllers;

use App\Models\AspekPenilaian;
use App\Models\ModulAjarAbsensi;
use App\Models\RaporCetak;
use App\Models\Siswa;
use App\Services\RaporService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResultController extends Controller
{
    public function __construct(private readonly RaporService $rapor) {}

    public function index()
    {
        return view('admin.result', [
            'aspekList' => AspekPenilaian::urut()->get(),
            'siswaList' => $this->kartuSiswa(),
        ]);
    }

    public function simpanAspek(Request $request): JsonResponse
    {
        $data = $this->validasiAspek($request);
        $data['urutan'] = $data['urutan'] ?? ((int) AspekPenilaian::max('urutan') + 1);

        $aspek = AspekPenilaian::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Aspek penilaian ditambahkan.',
            'data' => $aspek,
            'daftar' => AspekPenilaian::urut()->get(),
        ]);
    }

    public function ubahAspek(Request $request, AspekPenilaian $aspek): JsonResponse
    {
        $aspek->update($this->validasiAspek($request, $aspek));

        return response()->json([
            'status' => 'success',
            'message' => 'Aspek penilaian diperbarui.',
            'data' => $aspek->fresh(),
            'daftar' => AspekPenilaian::urut()->get(),
        ]);
    }

    public function hapusAspek(AspekPenilaian $aspek): JsonResponse
    {
        if ($aspek->sudahDipakai()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aspek ini sudah dipakai menilai anak, jadi tidak bisa dihapus. Nonaktifkan saja supaya tidak muncul lagi saat menilai, sementara rapor lama tetap utuh.',
            ], 422);
        }

        $aspek->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Aspek penilaian dihapus.',
            'daftar' => AspekPenilaian::urut()->get(),
        ]);
    }

    public function urutkanAspek(Request $request): JsonResponse
    {
        $data = $request->validate([
            'urutan' => 'required|array|min:1',
            'urutan.*' => 'required|integer|exists:aspek_penilaians,id',
        ]);

        foreach ($data['urutan'] as $posisi => $id) {
            AspekPenilaian::whereKey($id)->update(['urutan' => $posisi + 1]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Urutan aspek disimpan.',
            'daftar' => AspekPenilaian::urut()->get(),
        ]);
    }

    public function rapor(Siswa $siswa, Request $request): JsonResponse
    {
        $request->validate(['dari' => 'nullable|date', 'sampai' => 'nullable|date']);
        $terakhir = RaporCetak::terakhirUntuk($siswa->id);

        return response()->json([
            'status' => 'success',
            'data' => $this->rapor->untukSiswa($siswa, $request->query('dari'), $request->query('sampai')),
            'catatan_terakhir' => $terakhir?->only(['kekuatan', 'perbaikan', 'komentar', 'rencana']),
            'riwayat_cetak' => RaporCetak::where('siswa_id', $siswa->id)
                ->with('dicetakOleh:id,name')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (RaporCetak $c) => [
                    'id' => $c->id,
                    'periode' => $c->periode_label,
                    'jumlah_pertemuan' => count($c->pertemuan_ids ?? []),
                    'oleh' => $c->dicetakOleh?->name ?? '-',
                    'pada' => $c->created_at->translatedFormat('d M Y, H:i'),
                ]),
        ]);
    }

    public function cetakRapor(Siswa $siswa, Request $request)
    {
        $data = $this->validasiCetak($request, $siswa);
        $rapor = $this->rapor->untukSiswa($siswa, null, null, $data['pertemuan']);

        $catatan = RaporCetak::create([
            'siswa_id' => $siswa->id,
            'dicetak_oleh' => $request->user()?->id,
            'periode_label' => $rapor['periode']['label'],
            'pertemuan_ids' => array_map('intval', $data['pertemuan']),
            'kekuatan' => $data['kekuatan'] ?? null,
            'perbaikan' => $data['perbaikan'] ?? null,
            'komentar' => $data['komentar'] ?? null,
            'rencana' => $data['rencana'] ?? null,
        ]);

        $pdf = Pdf::loadView('pdf.rapor', [
            'rapor' => $rapor,
            'catatan' => $catatan->only(['kekuatan', 'perbaikan', 'komentar', 'rencana']),
            'dicetakPada' => now()->translatedFormat('d F Y, H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Rapor-'.Str::slug($siswa->name).'-'.now()->format('YmdHis').'.pdf');
    }

    public function cetakSertifikat(Siswa $siswa, Request $request)
    {
        $data = $this->validasiCetak($request, $siswa);

        $pdf = Pdf::loadView('pdf.sertifikat', [
            'rapor' => $this->rapor->untukSiswa($siswa, null, null, $data['pertemuan']),
            'judul' => $data['judul_sertifikat'] ?? 'Certificate of Achievement',
            'dicetakPada' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Sertifikat-'.Str::slug($siswa->name).'-'.now()->format('YmdHis').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasiCetak(Request $request, Siswa $siswa): array
    {
        $milikSiswa = ModulAjarAbsensi::where('siswa_id', $siswa->id)
            ->pluck('pertemuan_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $request->validate([
            'pertemuan' => 'required|array|min:1',
            'pertemuan.*' => ['required', 'integer', Rule::in($milikSiswa)],
            'judul_sertifikat' => 'nullable|string|max:120',
            'kekuatan' => 'nullable|string|max:2000',
            'perbaikan' => 'nullable|string|max:2000',
            'komentar' => 'nullable|string|max:2000',
            'rencana' => 'nullable|string|max:2000',
        ], [
            'pertemuan.required' => 'Pilih dulu pertemuan mana yang mau dicetak.',
            'pertemuan.min' => 'Pilih minimal satu pertemuan.',
            'pertemuan.*.in' => 'Ada pertemuan yang bukan milik siswa ini.',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kartuSiswa(): array
    {
        $absensis = ModulAjarAbsensi::query()
            ->with(['nilaiAspeks:id,modul_ajar_absensi_id,skor', 'pertemuan:id,tanggal,selesai_pada'])
            ->get()
            ->filter(fn (ModulAjarAbsensi $a) => $a->pertemuan?->selesai_pada !== null)
            ->groupBy('siswa_id');

        return Siswa::query()
            ->with('tingkatKemampuan:id,keterangan')
            ->orderBy('name')
            ->get(['id', 'name', 'panggilan', 'kelas', 'tingkat_kemampuan_id'])
            ->map(function (Siswa $siswa) use ($absensis) {
                $milik = $absensis->get($siswa->id, collect());
                $hadir = $milik->where('hadir', true);
                $nilai = $hadir->map(fn (ModulAjarAbsensi $a) => $a->rataAspek())->filter(fn ($n) => $n !== null);
                $terakhir = $milik
                    ->sortByDesc(fn (ModulAjarAbsensi $a) => $a->pertemuan->tanggal->toDateString())
                    ->first();

                return [
                    'id' => $siswa->id,
                    'nama' => $siswa->name,
                    'panggilan' => $siswa->panggilan,
                    'kelas' => $siswa->kelas,
                    'kemampuan' => $siswa->tingkatKemampuan?->keterangan,
                    'total_pertemuan' => $milik->count(),
                    'hadir' => $hadir->count(),
                    'persen_kehadiran' => $milik->count() > 0
                        ? (int) round($hadir->count() / $milik->count() * 100)
                        : 0,
                    'rata_nilai' => $nilai->isNotEmpty() ? round($nilai->avg(), 2) : null,
                    'terakhir_dinilai' => $terakhir?->pertemuan->tanggal->toDateString(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validasiAspek(Request $request, ?AspekPenilaian $aspek = null): array
    {
        $unik = 'unique:aspek_penilaians,nama'.($aspek ? ','.$aspek->id : '');

        return $request->validate([
            'nama' => ['required', 'string', 'max:120', $unik],
            'indikator' => 'required|string|max:255',
            'urutan' => 'nullable|integer|min:0|max:999',
            'aktif' => 'nullable|boolean',
        ], [
            'nama.required' => 'Nama aspek wajib diisi.',
            'nama.unique' => 'Sudah ada aspek dengan nama itu.',
            'indikator.required' => 'Indikator wajib diisi supaya guru tahu yang dinilai apa.',
        ]);
    }
}
