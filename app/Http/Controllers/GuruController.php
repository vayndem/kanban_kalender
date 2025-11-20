<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guru;
use Illuminate\Validation\ValidationException;

class GuruController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi: Nama guru wajib dan harus unik di tabel 'gurus'
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:gurus,name',
            ]);

            // Simpan data baru
            $guru = Guru::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Guru berhasil ditambahkan.',
                'data' => $guru
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
