<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Penggajian;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    public function index(): View
    {
        return view('admin.payroll', [
            'activeTab' => 'payroll',
            'ringkasan' => $this->payroll->ringkasan(),
            'riwayat' => $this->riwayat(),
        ]);
    }

    public function updateTarif(Request $request, Guru $guru): JsonResponse
    {
        $data = $request->validate([
            'gaji_bawaan' => 'required|integer|min:0',
            'gaji_per_kehadiran' => 'required|integer|min:0',
        ], [
            'gaji_bawaan.required' => 'Gaji bawaan wajib diisi.',
            'gaji_bawaan.integer' => 'Gaji bawaan harus berupa angka.',
            'gaji_bawaan.min' => 'Gaji bawaan tidak boleh negatif.',
            'gaji_per_kehadiran.required' => 'Gaji per kehadiran wajib diisi.',
            'gaji_per_kehadiran.integer' => 'Gaji per kehadiran harus berupa angka.',
            'gaji_per_kehadiran.min' => 'Gaji per kehadiran tidak boleh negatif.',
        ]);

        $guru->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Tarif gaji '.$guru->name.' berhasil disimpan.',
            'data' => $this->payroll->ringkasan(),
        ]);
    }

    public function jalankan(Guru $guru): JsonResponse
    {
        try {
            $struk = $this->payroll->jalankan($guru, auth()->user());
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Struk penggajian '.$guru->name.' berhasil diterbitkan.',
            'struk_id' => $struk->id,
            'data' => $this->payroll->ringkasan(),
            'riwayat' => $this->riwayat(),
        ]);
    }

    public function jalankanSemua(): JsonResponse
    {
        $hasil = $this->payroll->jalankanSemua(auth()->user());

        $pesan = $hasil['struk']->count().' struk penggajian diterbitkan.';
        if ($hasil['dilewati']->isNotEmpty()) {
            $pesan .= ' '.$hasil['dilewati']->count().' guru dilewati.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $pesan,
            'dilewati' => $hasil['dilewati']->values(),
            'data' => $this->payroll->ringkasan(),
            'riwayat' => $this->riwayat(),
        ]);
    }

    public function struk(Penggajian $penggajian): JsonResponse
    {
        $penggajian->load('guru:id,name', 'dijalankanOleh:id,name', 'dibatalkanOleh:id,name');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $penggajian->id,
                'guru' => $penggajian->guru?->name ?? '-',
                'jumlah_kehadiran' => $penggajian->jumlah_kehadiran,
                'gaji_bawaan' => $penggajian->gaji_bawaan,
                'gaji_per_kehadiran' => $penggajian->gaji_per_kehadiran,
                'total' => $penggajian->total,
                'dijalankan_pada' => $penggajian->dijalankan_pada?->toDateTimeString(),
                'dijalankan_oleh' => $penggajian->dijalankanOleh?->name,
                'dibatalkan_pada' => $penggajian->dibatalkan_pada?->toDateTimeString(),
                'dibatalkan_oleh' => $penggajian->dibatalkanOleh?->name,
                'alasan_batal' => $penggajian->alasan_batal,
                'log_kelas' => $this->payroll->logKelas($penggajian),
            ],
        ]);
    }

    public function batalkan(Request $request, Penggajian $penggajian): JsonResponse
    {
        $data = $request->validate([
            'alasan_batal' => 'required|string|max:255',
        ], [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
            'alasan_batal.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ]);

        try {
            $this->payroll->batalkan($penggajian, auth()->user(), $data['alasan_batal']);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Struk dibatalkan. Kehadirannya dikembalikan ke hitungan berjalan.',
            'data' => $this->payroll->ringkasan(),
            'riwayat' => $this->riwayat(),
        ]);
    }

    /**
     * Portal guru: hanya boleh melihat penggajian miliknya sendiri, read-only.
     */
    public function milikSaya(): View
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (! $guru) {
            return view('guru.belum-tertaut', ['user' => $user]);
        }

        return view('guru.gaji', ['gaji' => $this->payroll->ringkasanGuru($guru)]);
    }

    public function strukPdf(Penggajian $penggajian)
    {
        $this->pastikanBolehLihatStruk($penggajian);

        $penggajian->load('guru:id,name', 'dijalankanOleh:id,name', 'dibatalkanOleh:id,name');

        $pdf = Pdf::loadView('pdf.penggajian', [
            'struk' => $penggajian,
            'logKelas' => $this->payroll->logKelas($penggajian),
            'dicetakPada' => now()->translatedFormat('d F Y, H:i'),
        ])->setPaper('a4', 'portrait');

        $nama = Str::slug($penggajian->guru?->name ?? 'guru');

        return $pdf->download("Struk-Gaji-{$nama}-{$penggajian->id}.pdf");
    }

    /**
     * Admin boleh membuka struk siapa pun; guru hanya struk atas namanya sendiri.
     */
    private function pastikanBolehLihatStruk(Penggajian $penggajian): void
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return;
        }

        abort_unless($user->guru && $user->guru->id === $penggajian->guru_id, 403);
    }

    private function riwayat(): Collection
    {
        return Penggajian::query()
            ->with('guru:id,name')
            ->orderByDesc('dijalankan_pada')
            ->limit(50)
            ->get()
            ->map(fn (Penggajian $p) => [
                'id' => $p->id,
                'guru' => $p->guru?->name ?? '-',
                'jumlah_kehadiran' => $p->jumlah_kehadiran,
                'total' => $p->total,
                'dijalankan_pada' => $p->dijalankan_pada?->toDateTimeString(),
                'dibatalkan' => $p->sudahDibatalkan(),
            ])
            ->values();
    }
}
