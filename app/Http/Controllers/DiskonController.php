<?php

namespace App\Http\Controllers;

use App\Models\Diskon;
use App\Support\NomorHp;
use Illuminate\Http\Request;

class DiskonController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer|exists:diskons,id',
            'no_hp' => 'nullable|string',
            'diskon' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
            'is_universal' => 'required|boolean',
        ]);

        if (! empty($request->id)) {
            return $this->update($request, $request->id);
        }

        try {
            $isUniversal = $request->boolean('is_universal');
            $noHp = $isUniversal ? null : $this->kanonkan($request->no_hp);

            if (! $isUniversal && $noHp === null) {
                return $this->tolak($request, 'Nomor HP wajib diisi untuk diskon spesifik.');
            }

            $diskon = Diskon::updateOrCreate(
                ['no_hp' => $noHp],
                [
                    'diskon' => $request->diskon,
                    'keterangan' => $request->keterangan ?? ($isUniversal ? 'Diskon Massal' : 'Potongan Diskon Keluarga'),
                ]
            );

            $message = $isUniversal
                ? 'Diskon universal berhasil diterapkan ke seluruh siswa.'
                : 'Diskon berhasil diterapkan pada nomor HP ini.';

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'data' => $diskon,
                ]);
            }

            return redirect()->back()->with('success', 'Diskon berhasil diproses.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal memproses diskon: '.$e->getMessage()], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal memproses diskon.');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'no_hp' => 'nullable|string',
            'diskon' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
            'is_universal' => 'required|boolean',
        ]);

        try {
            $diskon = Diskon::find($id);

            if (! $diskon) {
                return $this->tidakDitemukan($request);
            }

            $isUniversal = $request->boolean('is_universal');
            $noHp = $isUniversal ? null : $this->kanonkan($request->no_hp);

            if (! $isUniversal && $noHp === null) {
                return $this->tolak($request, 'Nomor HP wajib diisi untuk diskon spesifik.');
            }

            $bentrok = Diskon::where('id', '!=', $diskon->id)
                ->when($isUniversal, fn ($q) => $q->whereNull('no_hp'), fn ($q) => $q->where('no_hp', $noHp))
                ->exists();

            if ($bentrok) {
                return $this->tolak($request, $isUniversal
                    ? 'Diskon massal sudah ada. Ubah baris diskon massal yang lama, jangan buat duplikat.'
                    : "Nomor HP {$noHp} sudah punya aturan diskon. Ubah baris diskon milik nomor itu, jangan buat duplikat.");
            }

            $diskon->update([
                'no_hp' => $noHp,
                'diskon' => $request->diskon,
                'keterangan' => $request->keterangan ?? ($isUniversal ? 'Diskon Massal' : 'Potongan Diskon Keluarga'),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Diskon berhasil diperbarui.',
                    'data' => $diskon,
                ]);
            }

            return redirect()->back()->with('success', 'Diskon berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui diskon: '.$e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal memperbarui diskon.');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $diskon = Diskon::find($id);

            if (! $diskon) {
                return $this->tidakDitemukan($request);
            }

            $diskon->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Diskon berhasil dihapus, kalkulasi tagihan kembali normal.',
                ]);
            }

            return redirect()->back()->with('success', 'Diskon berhasil dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal menghapus diskon: '.$e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal menghapus diskon.');
        }
    }

    private function kanonkan(?string $noHp): ?string
    {
        $noHp = trim((string) $noHp);

        if ($noHp === '') {
            return null;
        }

        return NomorHp::normalkan($noHp) ?? $noHp;
    }

    private function tolak(Request $request, string $pesan)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $pesan], 422);
        }

        return redirect()->back()->withInput()->with('error', $pesan);
    }

    private function tidakDitemukan(Request $request)
    {
        $pesan = 'Data diskon tidak ditemukan.';

        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $pesan], 404);
        }

        return redirect()->back()->with('error', $pesan);
    }
}
