<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Arsip;

class SiswaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:siswas,name',
            'panggilan' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama siswa sudah terdaftar di sistem.',
            'paket_pembayaran.exists' => 'Paket pembayaran yang dipilih tidak valid.',
        ]);

        try {
            $siswa = Siswa::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil ditambahkan.',
                    'data' => $siswa
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil ditambahkan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menyimpan', $e);
        }
    }

    public function update(Request $request, $id)
    {
        $siswa = Siswa::find($id);

        if (!$siswa) {
            return $this->handleNotFound($request, "Siswa (ID: $id)");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:siswas,name,' . $id,
            'panggilan' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'paket_pembayaran' => 'nullable|integer|exists:pakets,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama siswa sudah digunakan oleh data lain.',
            'paket_pembayaran.exists' => 'Paket pembayaran tidak ditemukan.',
        ]);

        try {
            $siswa->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Siswa berhasil diperbarui.',
                    'data' => $siswa
                ]);
            }

            return redirect()->back()->with('success', 'Siswa berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memperbarui', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $siswa = Siswa::find($id);

            if (!$siswa) {
                return $this->handleNotFound($request, "Siswa (ID: $id)");
            }

            // Proses Arsip
            Arsip::create([
                'name'             => $siswa->name,
                'panggilan'        => $siswa->panggilan,
                'kelas'            => $siswa->kelas,
                'no_hp'            => $siswa->no_hp,
                'paket_pembayaran' => $siswa->paket_pembayaran,
            ]);

            $siswa->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil diarsipkan dan dihapus.'
                ]);
            }

            return redirect()->back()->with('success', 'Data berhasil diarsipkan dan dihapus.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal mengarsipkan data', $e);
        }
    }

    private function handleNotFound($request, $item)
    {
        $msg = "Maaf, data $item tidak ditemukan. Silakan segarkan halaman.";
        return $request->wantsJson()
            ? response()->json(['status' => 'error', 'message' => $msg], 404)
            : redirect()->back()->with('error', $msg);
    }

    private function handleException($request, $prefix, $e)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $prefix . ': ' . $e->getMessage()
            ], 500);
        }
        return redirect()->back()->withInput()->with('error', $prefix . ': ' . $e->getMessage());
    }
}
