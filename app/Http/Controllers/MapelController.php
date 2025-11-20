<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matapelajaran; // Pastikan Anda mengimpor model yang sesuai

class MapelController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi data yang masuk
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:mata_pelajarans,name',
            ]);

            // Simpan data baru
            $mapel = Matapelajaran::create($validated);

            // Beri respons sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Mata Pelajaran berhasil ditambahkan.',
                'data' => $mapel
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangani error validasi
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Tangani error lain
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }
}
