<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tanda;
use Illuminate\Validation\ValidationException;

class TandaController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'siswa_id' => 'required|exists:siswas,id',
                'keterangan' => 'required|string',
            ]);

            $tanda = Tanda::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil ditambahkan.',
                'data' => $tanda,
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
            $tanda = Tanda::findOrFail($id);

            $validated = $request->validate([
                'siswa_id' => 'required|exists:siswas,id',
                'keterangan' => 'required|string',
            ]);

            $tanda->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil diperbarui.',
                'data' => $tanda,
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
            $tanda = Tanda::findOrFail($id);
            $tanda->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil dihapus.',
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
