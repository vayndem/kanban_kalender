<?php

namespace App\Http\Controllers;

use App\Models\Paket;
use Illuminate\Http\Request;

class PaketController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_paket' => 'required|string|max:255',
                'harga' => 'nullable|integer',
            ]);

            $paket = Paket::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Paket berhasil ditambahkan',
                'data' => $paket
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $paket = Paket::findOrFail($id);
            $validated = $request->validate([
                'nama_paket' => 'required|string|max:255',
                'harga' => 'nullable|integer',
            ]);

            $paket->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Paket berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Paket::findOrFail($id)->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Paket berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
