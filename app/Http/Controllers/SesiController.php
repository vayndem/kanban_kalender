<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sesi;

class SesiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sesis,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ], [
            'name.required' => 'Nama sesi wajib diisi.',
            'name.unique' => 'Nama sesi sudah digunakan.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai salah (gunakan HH:MM).',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.date_format' => 'Format jam selesai salah (gunakan HH:MM).',
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai.',
        ]);

        try {
            Sesi::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Sesi waktu berhasil ditambahkan.'
                ]);
            }

            return redirect()->back()->with('success', 'Sesi waktu berhasil ditambahkan.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $sesi = Sesi::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sesis,name,' . $id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ], [
            'name.required' => 'Nama sesi wajib diisi.',
            'name.unique' => 'Nama sesi sudah digunakan.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai salah (gunakan HH:MM).',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.date_format' => 'Format jam selesai salah (gunakan HH:MM).',
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai.',
        ]);

        try {
            $sesi->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Sesi waktu berhasil diperbarui.'
                ]);
            }

            return redirect()->back()->with('success', 'Sesi waktu berhasil diperbarui.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal memperbarui: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $sesi = Sesi::findOrFail($id);
            $sesi->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Sesi waktu berhasil dihapus.'
                ]);
            }

            return redirect()->back()->with('success', 'Sesi waktu berhasil dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
