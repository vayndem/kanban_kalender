<?php

namespace App\Http\Controllers;

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
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $pembayaran = Pembayaran::findOrFail($id);

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
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            Pembayaran::findOrFail($id)->delete();

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Data pembayaran dihapus.']);
            }

            return redirect()->back()->with('success', 'Data pembayaran dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal menghapus.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal menghapus.');
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
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function lunasPerSiswa(Request $request, $id_siswa)
    {
        try {
            Pembayaran::where('id_siswa', $id_siswa)
                ->where('status', 0)
                ->update(['status' => 1]);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Tagihan siswa berhasil ditagihkan.']);
            }

            return redirect()->back()->with('success', 'Tagihan siswa berhasil ditagihkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
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
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function penagihanMassal(Request $request)
    {
        try {
            $siswas = Siswa::whereNotNull('paket_pembayaran')->with('paket')->get();
            $count = 0;

            foreach ($siswas as $siswa) {
                if ($siswa->paket) {
                    Pembayaran::create([
                        'id_siswa' => $siswa->id,
                        'harga' => $siswa->paket->harga,
                        'keterangan' => 'Tagihan Paket ' . $siswa->paket->nama_paket . ' - ' . Carbon::now()->translatedFormat('F Y'),
                        'status' => 0
                    ]);
                    $count++;
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $count . ' Tagihan massal berhasil dibuat.'
                ]);
            }

            return redirect()->back()->with('success', $count . ' Tagihan massal berhasil dibuat.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
