<?php

namespace App\Http\Controllers;

use App\Models\TingkatKemampuan;
use Illuminate\Http\Request;

class TingkatKemampuanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'keterangan' => 'required|string|max:255',
        ], [
            'keterangan.required' => 'Keterangan wajib diisi.',
        ]);

        try {
            $tingkat = TingkatKemampuan::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Level {$tingkat->level} berhasil ditambahkan.",
                    'data' => $tingkat,
                ]);
            }

            return redirect()->back()->with('success', "Level {$tingkat->level} berhasil ditambahkan.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: '.$e->getMessage()], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $tingkat = TingkatKemampuan::findOrFail($id);

        $validated = $request->validate([
            'keterangan' => 'required|string|max:255',
        ], [
            'keterangan.required' => 'Keterangan wajib diisi.',
        ]);

        try {
            $tingkat->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Level {$tingkat->level} berhasil diperbarui.",
                    'data' => $tingkat,
                ]);
            }

            return redirect()->back()->with('success', "Level {$tingkat->level} berhasil diperbarui.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui: '.$e->getMessage()], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $tingkat = TingkatKemampuan::findOrFail($id);

            $levelTertinggi = (int) TingkatKemampuan::max('level');
            if ($tingkat->level !== $levelTertinggi) {
                $msg = "Level {$tingkat->level} tidak bisa dihapus dulu: hapus level {$levelTertinggi} (paling tinggi) dulu, baru turun satu-satu.";

                return $request->wantsJson()
                    ? response()->json(['status' => 'error', 'message' => $msg], 422)
                    : redirect()->back()->with('error', $msg);
            }

            $jumlahSiswa = $tingkat->siswas()->count();
            if ($jumlahSiswa > 0) {
                $msg = "Level {$tingkat->level} tidak bisa dihapus: masih dipakai {$jumlahSiswa} siswa. Ubah dulu kemampuan siswa tersebut di tab Siswa.";

                return $request->wantsJson()
                    ? response()->json(['status' => 'error', 'message' => $msg], 422)
                    : redirect()->back()->with('error', $msg);
            }

            $tingkat->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Level {$tingkat->level} berhasil dihapus.",
                ]);
            }

            return redirect()->back()->with('success', "Level {$tingkat->level} berhasil dihapus.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal menghapus: '.$e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal menghapus: '.$e->getMessage());
        }
    }
}
