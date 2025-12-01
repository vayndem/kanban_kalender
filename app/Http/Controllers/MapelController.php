<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MataPelajaran;
use Illuminate\Validation\ValidationException;

class MapelController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:mata_pelajarans,name',
            ]);

            $mapel = MataPelajaran::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Mata Pelajaran berhasil ditambahkan.',
                'data' => $mapel,
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Validasi gagal.',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $mapel = MataPelajaran::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:mata_pelajarans,name,' . $id,
                'border_color' => 'nullable|string|max:20',
            ]);

            $mapel->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Mata Pelajaran berhasil diperbarui.',
                'data' => $mapel,
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Validasi gagal.',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Gagal memperbarui: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function destroy($id)
    {
        try {
            $mapel = MataPelajaran::findOrFail($id);
            $mapel->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Mata Pelajaran berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Gagal menghapus: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
