<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MataPelajaran;

class MapelController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mata_pelajarans,name',
            'border_color' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama mata pelajaran wajib diisi.',
            'name.unique' => 'Mata pelajaran ini sudah ada dalam daftar.',
        ]);

        try {
            $mapel = MataPelajaran::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mata Pelajaran berhasil ditambahkan.',
                    'data' => $mapel,
                ]);
            }

            return redirect()->back()->with('success', 'Mata Pelajaran berhasil ditambahkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $mapel = MataPelajaran::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mata_pelajarans,name,' . $id,
            'border_color' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama mata pelajaran wajib diisi.',
            'name.unique' => 'Nama mata pelajaran sudah digunakan oleh data lain.',
        ]);

        try {
            $mapel->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mata Pelajaran berhasil diperbarui.',
                    'data' => $mapel,
                ]);
            }

            return redirect()->back()->with('success', 'Mata Pelajaran berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal memperbarui: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $mapel = MataPelajaran::findOrFail($id);
            $mapel->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mata Pelajaran berhasil dihapus.',
                ]);
            }

            return redirect()->back()->with('success', 'Mata Pelajaran berhasil dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
