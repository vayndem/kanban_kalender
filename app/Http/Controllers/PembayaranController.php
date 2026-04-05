<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Siswa;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_siswa' => 'required|exists:siswas,id',
                'harga' => 'required|integer',
                'keterangan' => 'nullable|string|max:255',
                'status'     => 'required|boolean',
            ]);

            $pembayaran = Pembayaran::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Data pembayaran berhasil dicatat.',
                'data' => $pembayaran,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $pembayaran = Pembayaran::findOrFail($id);
            $validated = $request->validate([
                'id_siswa' => 'required|exists:siswas,id',
                'harga' => 'required|integer',
                'keterangan' => 'nullable|string|max:255',
            ]);

            $pembayaran->update($validated);

            return response()->json(['status' => 'success', 'message' => 'Data pembayaran diperbarui.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Pembayaran::findOrFail($id)->delete();
            return response()->json(['status' => 'success', 'message' => 'Data pembayaran dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus.'], 500);
        }
    }

    public function lunasSemua()
    {
        try {
            Pembayaran::where('status', 0)->update(['status' => 1]);
            return response()->json(['status' => 'success', 'message' => 'Semua tagihan telah diselesaikan.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function lunasPerSiswa($id_siswa)
    {
        try {
            Pembayaran::where('id_siswa', $id_siswa)
                ->where('status', 0)
                ->update(['status' => 1]);

            return response()->json(['status' => 'success', 'message' => 'Tagihan siswa berhasil diselesaikan.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function penagihanMassal()
    {
        try {
            $siswas = Siswa::whereNotNull('paket_pembayaran')->with('paket')->get();
            $count = 0;

            foreach ($siswas as $siswa) {
                if ($siswa->paket) {
                    Pembayaran::create([
                        'id_siswa' => $siswa->id,
                        'harga' => $siswa->paket->harga,
                        'keterangan' => 'Pembayaran Paket ' . $siswa->paket->nama_paket,
                        'status' => 0
                    ]);
                    $count++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => $count . ' Tagihan massal berhasil dibuat.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
