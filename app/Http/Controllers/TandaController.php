<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tanda;
use Illuminate\Support\Facades\Validator;

class TandaController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'siswa_id' => 'required|exists:siswas,id',
            'keterangan' => 'required|string',
        ], [
            'siswa_id.required' => 'Siswa harus dipilih.',
            'siswa_id.exists' => 'Data siswa tidak ditemukan.',
            'keterangan.required' => 'Keterangan atau catatan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Tanda::create($validator->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil ditambahkan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'siswa_id' => 'required|exists:siswas,id',
            'keterangan' => 'required|string',
        ], [
            'siswa_id.required' => 'Siswa harus dipilih.',
            'siswa_id.exists' => 'Data siswa tidak ditemukan.',
            'keterangan.required' => 'Keterangan atau catatan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tanda = Tanda::findOrFail($id);
            $tanda->update($validator->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil diperbarui.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $tanda = Tanda::findOrFail($id);
            $tanda->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Tanda/Catatan berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }
}
