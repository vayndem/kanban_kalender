<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\Siswa;
use Illuminate\Http\Request;

class ArsipController extends Controller
{
    public function index(Request $request)
    {
        $arsips = Arsip::all();

        if ($request->wantsJson()) {
            return response()->json($arsips);
        }

        return view('admin.arsip.index', compact('arsips'));
    }

    public function show(Request $request, Arsip $arsip)
    {
        if ($request->wantsJson()) {
            return response()->json($arsip);
        }

        return view('admin.arsip.show', compact('arsip'));
    }

    public function update(Request $request, Arsip $arsip)
    {
        try {
            Siswa::create([
                'name' => $arsip->name,
                'panggilan' => $arsip->panggilan,
                'kelas' => $arsip->kelas,
                'no_hp' => $arsip->no_hp,
                'paket_pembayaran' => $arsip->paket_pembayaran,
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
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengembalikan data: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal mengembalikan data: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Arsip $arsip)
    {
        try {
            $arsip->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil dihapus permanen dari arsip.',
                ]);
            }

            return redirect()->back()->with('success', 'Data arsip berhasil dihapus permanen.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }
    }
}
