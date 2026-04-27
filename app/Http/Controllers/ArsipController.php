<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\Siswa;
use Illuminate\Http\Request;

class ArsipController extends Controller
{
    public function index()
    {
        $arsips = Arsip::all();
        return response()->json($arsips);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Arsip $arsip)
    {
        return response()->json($arsip);
    }

    public function edit(Arsip $arsip)
    {
        //
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

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dikembalikan ke tabel siswa.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengembalikan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Arsip $arsip)
    {
        try {
            $arsip->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dihapus permanen dari arsip.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
