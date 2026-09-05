<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:gurus,name',
        ], [
            'name.required' => 'Nama guru wajib diisi.',
            'name.unique' => 'Nama guru sudah terdaftar di sistem.',
        ]);

        try {
            $guru = Guru::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Guru berhasil ditambahkan.',
                    'data' => $guru,
                ]);
            }

            return redirect()->back()->with('success', 'Guru berhasil ditambahkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:gurus,name,'.$id,
        ], [
            'name.required' => 'Nama guru wajib diisi.',
            'name.unique' => 'Nama guru sudah digunakan oleh data lain.',
        ]);

        try {
            $guru->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Guru berhasil diperbarui.',
                    'data' => $guru,
                ]);
            }

            return redirect()->back()->with('success', 'Guru berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal memperbarui: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $guru = Guru::findOrFail($id);

            $jumlahJadwal = $guru->jadwals()->count();
            if ($jumlahJadwal > 0) {
                $msg = "Guru {$guru->name} tidak bisa dihapus: masih mengajar {$jumlahJadwal} baris jadwal. "
                    .'Hapus atau pindahkan jadwalnya dulu di tab Jadwal Pelajaran, baru guru ini bisa dihapus.';

                return $request->wantsJson()
                    ? response()->json(['status' => 'error', 'message' => $msg], 422)
                    : redirect()->back()->with('error', $msg);
            }

            if (AbsensiGuru::where('guru_id', $guru->id)->exists()) {
                $msg = "Guru {$guru->name} tidak bisa dihapus: sudah punya riwayat absen mengajar di Modul Ajar.";

                return $request->wantsJson()
                    ? response()->json(['status' => 'error', 'message' => $msg], 422)
                    : redirect()->back()->with('error', $msg);
            }

            $guru->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Guru berhasil dihapus.',
                ]);
            }

            return redirect()->back()->with('success', 'Guru berhasil dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menghapus: '.$e->getMessage());
        }
    }
}
