<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa; // Pastikan model Siswa diimpor
use Illuminate\Validation\ValidationException;

class SiswaController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi: Nama siswa wajib dan harus unik di tabel 'siswas'
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:siswas,name',
            ]);

            // Simpan data baru
            $siswa = Siswa::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Siswa berhasil ditambahkan.',
                'data' => $siswa
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }
}
