<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\Siswa;
use Illuminate\Http\Request;

class ArsipController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(Arsip::orderBy('name')->get());
        }

        return redirect()->route('dashboard', ['tab' => 'data_siswa']);
    }

    public function update(Request $request, $id)
    {
        $arsip = Arsip::find($id);

        if (! $arsip) {
            return $this->handleNotFound($request, 'Arsip');
        }

        try {
            Siswa::create([
                'name' => $arsip->name,
                'panggilan' => $arsip->panggilan,
                'kelas' => $arsip->kelas,
                'no_hp' => $arsip->no_hp,
                'paket_pembayaran' => $arsip->paket_pembayaran,
                'paket_pembayaran_2' => $arsip->paket_pembayaran_2,
                'paket_pembayaran_3' => $arsip->paket_pembayaran_3,
                'paket_pembayaran_4' => $arsip->paket_pembayaran_4,
                'paket_pembayaran_5' => $arsip->paket_pembayaran_5,
                'tingkat_kemampuan_id' => $arsip->tingkat_kemampuan_id,
            ]);

            $arsip->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil dikembalikan ke tabel siswa.',
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil dikembalikan dari arsip.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal mengembalikan data', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $arsip = Arsip::find($id);

            if (! $arsip) {
                return $this->handleNotFound($request, 'Arsip');
            }

            $arsip->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil dihapus permanen dari arsip.',
                ]);
            }

            return redirect()->back()->with('success', 'Data arsip berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menghapus data', $e);
        }
    }

    private function handleNotFound($request, $item)
    {
        $msg = "Maaf, data $item tidak ditemukan. Silakan segarkan halaman browser Anda.";
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 404);
        }

        return redirect()->back()->with('error', $msg);
    }

    private function handleException($request, $prefix, $e)
    {
        $msg = $prefix.': '.$e->getMessage();
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 500);
        }

        return redirect()->back()->withInput()->with('error', $msg);
    }
}
