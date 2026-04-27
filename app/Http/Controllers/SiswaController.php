<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use Illuminate\Validation\ValidationException;
use App\Models\Arsip;

class SiswaController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:siswas,name',
                'panggilan' => 'nullable|string|max:100',
                'kelas' => 'nullable|string|max:50',
                'no_hp' => 'nullable|string|max:20',
                'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
            ]);

            $siswa = Siswa::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Siswa berhasil ditambahkan.',
                'data' => $siswa,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $siswa = Siswa::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:siswas,name,' . $id,
                'panggilan' => 'nullable|string|max:100',
                'kelas' => 'nullable|string|max:50',
                'no_hp' => 'nullable|string|max:20',
                'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
            ]);

            $siswa->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Siswa berhasil diperbarui.',
                'data' => $siswa,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $siswa = Siswa::findOrFail($id);

            Arsip::create([
                'name'             => $siswa->name,
                'panggilan'        => $siswa->panggilan,
                'kelas'            => $siswa->kelas,
                'no_hp'            => $siswa->no_hp,
                'paket_pembayaran' => $siswa->paket_pembayaran,
            ]);

            $siswa->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dipindahkan ke arsip dan dihapus dari sistem utama.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengarsipkan data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
