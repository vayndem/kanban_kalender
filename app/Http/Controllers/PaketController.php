<?php

namespace App\Http\Controllers;

use App\Models\Paket;
use Illuminate\Http\Request;

class PaketController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_paket' => 'required|string|max:255',
            'harga' => 'nullable|integer',
            'pertemuan' => 'required|integer|min:1',
        ], [
            'nama_paket.required' => 'Nama paket wajib diisi.',
            'pertemuan.required' => 'Jumlah pertemuan wajib diisi.',
        ]);

        try {
            $paket = Paket::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Paket berhasil ditambahkan',
                    'data' => $paket
                ]);
            }

            return redirect()->back()->with('success', 'Paket berhasil ditambahkan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menyimpan', $e);
        }
    }

    public function update(Request $request, $id)
    {
        $paket = Paket::find($id);

        if (!$paket) {
            return $this->handleNotFound($request, "Paket (ID: $id)");
        }

        $validated = $request->validate([
            'nama_paket' => 'required|string|max:255',
            'harga' => 'nullable|integer',
            'pertemuan' => 'required|integer|min:1',
        ], [
            'nama_paket.required' => 'Nama paket wajib diisi.',
            'pertemuan.required' => 'Jumlah pertemuan wajib diisi.',
        ]);

        try {
            $paket->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Paket berhasil diperbarui'
                ]);
            }

            return redirect()->back()->with('success', 'Paket berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memperbarui', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $paket = Paket::find($id);

            if (!$paket) {
                return $this->handleNotFound($request, "Paket");
            }

            $paket->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Paket berhasil dihapus'
                ]);
            }

            return redirect()->back()->with('success', 'Paket berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menghapus', $e);
        }
    }

    private function handleNotFound($request, $item)
    {
        $msg = "Maaf, data $item tidak ditemukan. Silakan segarkan halaman browser Anda.";
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 404);
        }
        return redirect()->back()->with('error', $msg);
    }

    private function handleException($request, $prefix, $e)
    {
        $msg = $prefix . ': ' . $e->getMessage();
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 500);
        }
        return redirect()->back()->withInput()->with('error', $msg);
    }
}
