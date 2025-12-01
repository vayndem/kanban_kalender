<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sesi;
use Illuminate\Validation\ValidationException;

class SesiController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:sesis,name',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $sesi = Sesi::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Sesi waktu berhasil ditambahkan.',
                'data' => $sesi,
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
            $sesi = Sesi::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:sesis,name,' . $id,
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $sesi->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Sesi waktu berhasil diperbarui.',
                'data' => $sesi,
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
            $sesi = Sesi::findOrFail($id);
            $sesi->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Sesi waktu berhasil dihapus.',
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
