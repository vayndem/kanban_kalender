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
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:gurus,name',
            ]);

            $guru = Guru::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Guru berhasil ditambahkan.',
                'data' => $guru,
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
            $guru = Guru::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:gurus,name,' . $id,
            ]);

            $guru->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Guru berhasil diperbarui.',
                'data' => $guru,
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
            $guru = Guru::findOrFail($id);
            $guru->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Guru berhasil dihapus.',
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
