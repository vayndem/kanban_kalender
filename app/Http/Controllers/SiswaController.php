<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Arsip;
use App\Models\Jadwal;

class SiswaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:siswas,name',
            'panggilan' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama siswa sudah terdaftar di sistem.',
            'paket_pembayaran.exists' => 'Paket pembayaran yang dipilih tidak valid.',
        ]);

        try {
            $siswa = Siswa::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil ditambahkan.',
                    'data' => $siswa
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

        if (!$siswa) {
            return $this->handleNotFound($request, "Siswa (ID: $id)");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:siswas,name,' . $id,
            'panggilan' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama siswa sudah digunakan oleh data lain.',
            'paket_pembayaran.exists' => 'Paket pembayaran tidak ditemukan.',
        ]);

        try {
            $siswa->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil diperbarui.',
                    'data' => $siswa
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memperbarui', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $siswa = Siswa::find($id);

            if (!$siswa) {
                return $this->handleNotFound($request, "Siswa");
            }

            Arsip::create([
                'name'             => $siswa->name,
                'panggilan'        => $siswa->panggilan,
                'kelas'            => $siswa->kelas,
                'no_hp'            => $siswa->no_hp,
                'paket_pembayaran' => $siswa->paket_pembayaran,
            ]);

            Jadwal::where('siswa_id', $id)->delete();
            $siswa->tandas()->delete();
            $siswa->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil diarsipkan dan jadwal telah dibersihkan.'
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil diarsipkan.');
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
                'message' => $prefix . ': ' . $e->getMessage()
            ], 500);
        }
        return redirect()->back()->withInput()->with('error', $prefix . ': ' . $e->getMessage());
    }

    public function exportPdf(Request $request)
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

        $siswas = $query->get();

        $filterLabel = $this->buildFilterLabel($request);

        $pdf = Pdf::loadView('pdf.siswa', [
            'siswas'      => $siswas,
            'filterLabel' => $filterLabel,
            'exportedAt'  => now()->translatedFormat('d F Y, H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Data-Siswa-' . now()->format('YmdHis') . '.pdf');
    }

    private function buildFilterLabel(Request $request): string
    {
        $parts = [];

        if ($request->filled('kelas'))    $parts[] = 'Kelas: ' . $request->kelas;
        if ($request->filled('paket_id')) {
            $paket = \App\Models\Paket::find($request->paket_id);
            $parts[] = 'Paket: ' . ($paket ? $paket->nama_paket : $request->paket_id);
        }
        if ($request->filled('sesi_ids')) {
            $ids   = array_filter(explode(',', $request->sesi_ids));
            $names = \App\Models\Sesi::whereIn('id', $ids)->pluck('name')->join(', ');
            $parts[] = 'Sesi: ' . $names;
        }
        if ($request->filled('guru_ids')) {
            $ids   = array_filter(explode(',', $request->guru_ids));
            $names = \App\Models\Guru::whereIn('id', $ids)->pluck('name')->join(', ');
            $parts[] = 'Guru: ' . $names;
        }
        if ($request->filled('search'))   $parts[] = 'Cari: "' . $request->search . '"';

        return $parts ? implode(' • ', $parts) : 'Semua Siswa';
    }
}
