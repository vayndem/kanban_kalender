<?php

namespace App\Http\Controllers;

use App\Exports\PembayaranExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Siswa;
use Carbon\Carbon;

class PembayaranController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswas,id',
            'harga' => 'required|integer',
            'keterangan' => 'nullable|string|max:255',
            'status' => 'required|integer|in:0,1,2',
        ], [
            'id_siswa.exists' => 'Siswa tidak ditemukan.',
            'status.in' => 'Status pembayaran tidak valid.',
        ]);

        try {
            $pembayaran = Pembayaran::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data pembayaran berhasil dicatat.',
                    'data' => $pembayaran,
                ]);
            }

            return redirect()->back()->with('success', 'Data pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menyimpan', $e);
        }
    }

    public function update(Request $request, $id)
    {
        // Gunakan find() agar tidak melempar error 500 jika ID salah
        $pembayaran = Pembayaran::find($id);

        if (!$pembayaran) {
            return $this->handleNotFound($request, "Pembayaran (ID: $id)");
        }

        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswas,id',
            'harga' => 'required|integer',
            'keterangan' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:0,1,2',
        ]);

        try {
            $pembayaran->update($validated);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Data pembayaran diperbarui.']);
            }

            return redirect()->back()->with('success', 'Data pembayaran diperbarui.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memperbarui', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $pembayaran = Pembayaran::find($id);

            if (!$pembayaran) {
                return $this->handleNotFound($request, "Pembayaran");
            }

            $pembayaran->delete();

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Data pembayaran dihapus.']);
            }

            return redirect()->back()->with('success', 'Data pembayaran dihapus.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menghapus', $e);
        }
    }

    public function lunasSemua(Request $request)
    {
        try {
            Pembayaran::whereIn('status', [0, 1])->update([
                'status' => 2,
                'tanggal_pembayaran' => Carbon::now(),
                'pembayaran_via' => 0
            ]);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Semua tagihan telah diselesaikan.']);
            }

            return redirect()->back()->with('success', 'Semua tagihan telah diselesaikan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses pelunasan massal', $e);
        }
    }

    public function lunasPerSiswa(Request $request, $id_siswa)
    {
        try {
            if (!Siswa::where('id', $id_siswa)->exists()) {
                return $this->handleNotFound($request, "Siswa");
            }

            Pembayaran::where('id_siswa', $id_siswa)
                ->where('status', 0)
                ->update(['status' => 1]);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Tagihan siswa berhasil ditagihkan.']);
            }

            return redirect()->back()->with('success', 'Tagihan siswa berhasil ditagihkan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses tagihan siswa', $e);
        }
    }

    public function bayarPerSiswa(Request $request, $id_siswa)
    {
        try {
            Pembayaran::where('id_siswa', $id_siswa)
                ->where('status', 1)
                ->update([
                    'status' => 2,
                    'tanggal_pembayaran' => $request->tanggal_pembayaran ?? Carbon::now(),
                    'pembayaran_via' => $request->pembayaran_via ?? 0
                ]);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Pembayaran siswa berhasil diproses.']);
            }

            return redirect()->back()->with('success', 'Pembayaran siswa berhasil diproses.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses pembayaran', $e);
        }
    }

    public function penagihanMassal(Request $request)
    {
        try {
            $siswas = Siswa::whereNotNull('paket_pembayaran')->with('paket')->get();
            $count = 0;
            $now = Carbon::now();
            $bulanTahun = $now->translatedFormat('F Y');

            foreach ($siswas as $siswa) {
                if ($siswa->paket) {
                    Pembayaran::create([
                        'id_siswa' => $siswa->id,
                        'harga' => $siswa->paket->harga,
                        'keterangan' => "Tagihan Paket {$siswa->paket->nama_paket} - {$bulanTahun}",
                        'status' => 0
                    ]);
                    $count++;
                }
            }

            $message = "{$count} Tagihan massal berhasil dibuat.";
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal membuat tagihan massal', $e);
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
        $msg = $prefix . ': ' . $e->getMessage();
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 500);
        }
        return redirect()->back()->withInput()->with('error', $msg);
    }

    public function exportExcel(Request $request)
    {
        $fileName = 'Laporan_Pembayaran_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new PembayaranExport($request), $fileName);
    }
}
