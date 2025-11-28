<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use Illuminate\Validation\ValidationException;

class SiswaController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi: Menambahkan aturan untuk 'panggilan' dan 'kelas'
            $validated = $request->validate([
                'name'      => 'required|string|max:255|unique:siswas,name',
                'panggilan' => 'nullable|string|max:100',
                'kelas'     => 'nullable|string|max:50',
            ]);

            // Simpan data baru (otomatis map ke kolom database karena nama field sama)
            $siswa = Siswa::create($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Siswa berhasil ditambahkan.',
                'data'    => $siswa
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }
}
