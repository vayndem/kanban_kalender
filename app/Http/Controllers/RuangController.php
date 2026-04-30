<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ruang;

class RuangController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ruangs,name',
        ], [
            'name.required' => 'Nama ruang wajib diisi.',
            'name.unique' => 'Nama ruang sudah digunakan.',
        ]);

        try {
            $ruang = Ruang::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Ruang berhasil ditambahkan.',
                    'data' => $ruang,
                ]);
            }

            return redirect()->back()->with('success', 'Ruang berhasil ditambahkan.');
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
        $ruang = Ruang::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ruangs,name,' . $id,
        ], [
            'name.required' => 'Nama ruang wajib diisi.',
            'name.unique' => 'Nama ruang sudah digunakan.',
        ]);

        try {
            $ruang->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Ruang berhasil diperbarui.',
                    'data' => $ruang,
                ]);
            }

            return redirect()->back()->with('success', 'Ruang berhasil diperbarui.');
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
            $ruang = Ruang::findOrFail($id);
            $ruang->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Ruang berhasil dihapus.',
                ]);
            }

            return redirect()->back()->with('success', 'Ruang berhasil dihapus.');
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
