<?php

// app/Http/Controllers/SesiController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sesi; // Pastikan model Sesi diimpor
use Illuminate\Validation\ValidationException;

class SesiController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi: Membutuhkan nama, waktu mulai, dan waktu selesai
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:sesis,name',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time', // Memastikan waktu selesai setelah waktu mulai
            ]);

            // Simpan data baru
            $sesi = Sesi::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Sesi waktu berhasil ditambahkan.',
                'data' => $sesi
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
