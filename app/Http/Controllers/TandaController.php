<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tanda;

class TandaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'keterangan' => 'required|string',
        ], [
            'siswa_id.required' => 'Siswa harus dipilih.',
            'siswa_id.exists' => 'Data siswa tidak ditemukan.',
            'keterangan.required' => 'Keterangan atau catatan wajib diisi.',
        ]);

        try {
            Tanda::create($validated);
            return redirect()->back()->with('success', 'Tanda/Catatan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $tanda = Tanda::findOrFail($id);

        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'keterangan' => 'required|string',
        ], [
            'siswa_id.required' => 'Siswa harus dipilih.',
            'siswa_id.exists' => 'Data siswa tidak ditemukan.',
            'keterangan.required' => 'Keterangan atau catatan wajib diisi.',
        ]);

        try {
            $tanda->update($validated);
            return redirect()->back()->with('success', 'Tanda/Catatan berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $tanda = Tanda::findOrFail($id);
            $tanda->delete();

            return redirect()->back()->with('success', 'Tanda/Catatan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
