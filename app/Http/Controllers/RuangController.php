<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ruang;
use Illuminate\Validation\ValidationException;

class RuangController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ruangs,name',
            ]);

            $ruang = Ruang::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Ruang berhasil ditambahkan.',
                'data' => $ruang,
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
            $ruang = Ruang::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ruangs,name,' . $id,
            ]);

            $ruang->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Ruang berhasil diperbarui.',
                'data' => $ruang,
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
            $ruang = Ruang::findOrFail($id);
            $ruang->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Ruang berhasil dihapus.',
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
