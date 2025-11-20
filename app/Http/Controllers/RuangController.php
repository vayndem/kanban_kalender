<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ruang; // Pastikan model Ruang diimpor
use Illuminate\Validation\ValidationException;

class RuangController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi: Nama ruang wajib dan harus unik di tabel 'ruangs'
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ruangs,name',
            ]);

            // Simpan data baru
            $ruang = Ruang::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Ruang berhasil ditambahkan.',
                'data' => $ruang
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
