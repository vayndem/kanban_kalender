<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tanda; // Pastikan Model Tanda di-import
use Illuminate\Support\Facades\Log;

class TandaController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'siswa_id' => 'required|exists:siswas,id', // Pastikan ID siswa ada di tabel siswas
                'keterangan' => 'required|string',
            ]);

            // Simpan ke database
            Tanda::create([
                'siswa_id' => $validated['siswa_id'],
                'keterangan' => $validated['keterangan'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil ditambahkan ke siswa.'
            ]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan tanda: ' . $e->getMessage()
            ], 500);
        }
    }
}
