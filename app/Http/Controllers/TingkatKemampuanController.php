<?php

namespace App\Http\Controllers;

use App\Models\TingkatKemampuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TingkatKemampuanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate(
            ['keterangan' => ['required', 'string', 'max:255', Rule::unique('tingkat_kemampuans', 'keterangan')]],
            $this->pesan()
        );

        try {
            $tingkat = TingkatKemampuan::create($validated);

            return $this->balas($request, "Kemampuan \"{$tingkat->keterangan}\" berhasil ditambahkan.", $tingkat);
        } catch (\Exception $e) {
            return $this->gagal($request, 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $tingkat = TingkatKemampuan::findOrFail($id);

        $validated = $request->validate(
            ['keterangan' => ['required', 'string', 'max:255', Rule::unique('tingkat_kemampuans', 'keterangan')->ignore($tingkat->id)]],
            $this->pesan()
        );

        try {
            $tingkat->update($validated);

            return $this->balas($request, "Kemampuan \"{$tingkat->keterangan}\" berhasil diperbarui.", $tingkat);
        } catch (\Exception $e) {
            return $this->gagal($request, 'Gagal memperbarui: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $tingkat = TingkatKemampuan::findOrFail($id);

            $jumlahSiswa = $tingkat->siswas()->count();
            if ($jumlahSiswa > 0) {
                $msg = "Kemampuan \"{$tingkat->keterangan}\" tidak bisa dihapus: masih dipakai {$jumlahSiswa} siswa. Ubah dulu kemampuan siswa tersebut di tab Siswa.";

                return $request->wantsJson()
                    ? response()->json(['status' => 'error', 'message' => $msg], 422)
                    : redirect()->back()->with('error', $msg);
            }

            $keterangan = $tingkat->keterangan;
            $tingkat->delete();

            return $this->balas($request, "Kemampuan \"{$keterangan}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return $this->gagal($request, 'Gagal menghapus: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function pesan(): array
    {
        return [
            'keterangan.required' => 'Sebutan kemampuan wajib diisi.',
            'keterangan.unique' => 'Sebutan kemampuan itu sudah ada di daftar.',
        ];
    }

    private function balas(Request $request, string $pesan, ?TingkatKemampuan $tingkat = null)
    {
        if ($request->wantsJson()) {
            return response()->json(array_filter([
                'status' => 'success',
                'message' => $pesan,
                'data' => $tingkat,
            ], fn ($nilai) => $nilai !== null));
        }

        return redirect()->back()->with('success', $pesan);
    }

    private function gagal(Request $request, string $pesan)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $pesan], 500);
        }

        return redirect()->back()->withInput()->with('error', $pesan);
    }
}
