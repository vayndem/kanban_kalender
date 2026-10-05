<?php

namespace App\Http\Controllers;

use App\Models\KetersediaanGuru;
use App\Services\KetersediaanGuruService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KetersediaanGuruController extends Controller
{
    public function __construct(private readonly KetersediaanGuruService $ketersediaan) {}

    public function store(Request $request): JsonResponse
    {
        $data = $this->validasi($request);

        $kembar = KetersediaanGuru::where('guru_id', $data['guru_id'])
            ->where('hari_id', $data['hari_id'])
            ->where('jam_mulai', $data['jam_mulai'])
            ->where('jam_selesai', $data['jam_selesai'])
            ->exists();

        if ($kembar) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penanda yang sama persis sudah ada untuk guru dan hari itu.',
            ], 422);
        }

        KetersediaanGuru::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Penanda tidak tersedia berhasil disimpan.',
            'data' => $this->ketersediaan->daftarPerGuru(),
        ]);
    }

    public function destroy(KetersediaanGuru $ketersediaan): JsonResponse
    {
        $ketersediaan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Penanda tidak tersedia dihapus.',
            'data' => $this->ketersediaan->daftarPerGuru(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'guru_id' => ['required', 'integer', 'exists:gurus,id'],
            'hari_id' => ['required', 'integer', 'exists:haris,id'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ], [
            'guru_id.required' => 'Pilih dulu gurunya.',
            'hari_id.required' => 'Pilih dulu harinya.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Jam mulai harus berformat jam:menit, misalnya 17:00.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Jam selesai harus berformat jam:menit, misalnya 21:00.',
            'jam_selesai.after' => 'Jam selesai harus lewat dari jam mulai.',
        ]);
    }
}
