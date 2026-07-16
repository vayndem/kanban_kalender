<?php

namespace App\Http\Controllers;

use App\Models\Diskon;
use Illuminate\Http\Request;

class DiskonController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:diskons,id',
            'no_hp' => 'nullable|string',
            'diskon' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
            'is_universal' => 'required|boolean'
        ]);

        try {
            $isUniversal = $request->boolean('is_universal');
            $noHp = $isUniversal ? null : $request->no_hp;

            if (!$isUniversal && empty($noHp)) {
                return response()->json(['status' => 'error', 'message' => 'Nomor HP wajib diisi untuk diskon spesifik.'], 422);
            }

            if (!empty($request->id)) {
                $diskon = Diskon::find($request->id);
                if ($diskon) {
                    $diskon->update([
                        'no_hp' => $noHp,
                        'diskon' => $request->diskon,
                        'keterangan' => $request->keterangan ?? ($isUniversal ? 'Diskon Massal' : 'Potongan Diskon Keluarga')
                    ]);
                    $message = 'Diskon berhasil diperbarui.';
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Data diskon tidak ditemukan.'], 404);
                }
            } else {
                if ($isUniversal) {
                    $diskon = Diskon::updateOrCreate(
                        ['no_hp' => null],
                        [
                            'diskon' => $request->diskon,
                            'keterangan' => $request->keterangan ?? 'Diskon Massal'
                        ]
                    );
                    $message = 'Diskon universal berhasil diterapkan ke seluruh siswa.';
                } else {
                    $diskon = Diskon::updateOrCreate(
                        ['no_hp' => $noHp],
                        [
                            'diskon' => $request->diskon,
                            'keterangan' => $request->keterangan ?? 'Potongan Diskon Keluarga'
                        ]
                    );
                    $message = 'Diskon berhasil diterapkan pada nomor HP ini.';
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'data' => $diskon
                ]);
            }

            return redirect()->back()->with('success', 'Diskon berhasil diproses.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal memproses diskon: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal memproses diskon.');
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'no_hp' => 'nullable|string',
            'diskon' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
            'is_universal' => 'required|boolean'
        ]);

        try {
            $diskon = Diskon::find($id);

            if (!$diskon) {
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => 'Data diskon tidak ditemukan.'], 404);
                }
                return redirect()->back()->with('error', 'Data diskon tidak ditemukan.');
            }

            $isUniversal = $request->boolean('is_universal');
            $noHp = $isUniversal ? null : $request->no_hp;

            $diskon->update([
                'no_hp' => $noHp,
                'diskon' => $request->diskon,
                'keterangan' => $request->keterangan ?? ($isUniversal ? 'Diskon Massal' : 'Potongan Diskon Keluarga')
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Diskon berhasil diperbarui.',
                    'data' => $diskon
                ]);
            }

            return redirect()->back()->with('success', 'Diskon berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui diskon: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal memperbarui diskon.');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $diskon = Diskon::find($id);

            if (!$diskon) {
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => 'Data diskon tidak ditemukan.'], 404);
                }
                return redirect()->back()->with('error', 'Data diskon tidak ditemukan.');
            }

            $diskon->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Diskon berhasil dihapus, kalkulasi tagihan kembali normal.'
                ]);
            }

            return redirect()->back()->with('success', 'Diskon berhasil dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Gagal menghapus diskon: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menghapus diskon.');
        }
    }
}
