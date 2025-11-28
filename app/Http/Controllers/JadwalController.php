<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jadwal;
use App\Models\Hari;
use App\Models\Sesi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Tanda;

class JadwalController extends Controller
{
    public function tampilKalender()
    {
        $haris = Hari::orderBy('id')->get();
        $sesis = Sesi::orderBy('id')->get();

        $jadwalsData = Jadwal::with(['siswa', 'mataPelajaran', 'guru', 'ruang'])->get();

        $finalJadwals = [];
        foreach ($jadwalsData as $jadwal) {
            $classKey = $jadwal->mata_pelajaran_id . '_' . $jadwal->guru_id . '_' . $jadwal->ruang_id;

            if (!isset($finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey])) {
                $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey] = [
                    'mapel' => $jadwal->mataPelajaran,
                    'guru' => $jadwal->guru,
                    'ruang' => $jadwal->ruang,
                    'siswa_list' => collect(),
                ];
            }

            $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey]['siswa_list']->push($jadwal->siswa);
        }

        return view('jadwal_kalender', [
            'haris' => $haris,
            'sesis' => $sesis,
            'jadwals' => $finalJadwals,
        ]);
    }

    public function updatePosisi(Request $request)
    {
        $request->validate([
            'mapel_id' => 'required|integer',
            'guru_id' => 'required|integer',
            'ruang_id' => 'required|integer',
            'old_hari_id' => 'required|integer',
            'old_sesi_id' => 'required|integer',
            'new_hari_id' => 'required|integer',
            'new_sesi_id' => 'required|integer',
        ]);

        try {
            $affectedRows = Jadwal::where('mata_pelajaran_id', $request->mapel_id)
                ->where('guru_id', $request->guru_id)
                ->where('ruang_id', $request->ruang_id)
                ->where('hari_id', $request->old_hari_id)
                ->where('sesi_id', $request->old_sesi_id)
                ->update([
                    'hari_id' => $request->new_hari_id,
                    'sesi_id' => $request->new_sesi_id,
                    'updated_at' => now(),
                ]);

            if ($affectedRows > 0) {
                return response()->json(['status' => 'success', 'message' => 'Jadwal berhasil dipindahkan.']);
            } else {
                return response()->json(['status' => 'warning', 'message' => 'Tidak ada jadwal yang dipindahkan.'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateKelas(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            // Validasi data jadwal lama (untuk referensi hapus)
            'old_mapel_id' => 'required|integer',
            'old_guru_id' => 'required|integer',
            'old_ruang_id' => 'required|integer',
            'old_hari_id' => 'required|integer',
            'old_sesi_id' => 'required|integer',

            // Validasi data jadwal baru (untuk insert ulang)
            'mapel_id' => 'required|integer',
            'guru_id' => 'required|integer',
            'ruang_id' => 'required|integer',
            'siswa_ids' => 'present|array',
            'siswa_ids.*' => 'integer',

            // === [BARU] Validasi Array ID Tanda yang akan dihapus ===
            'deleted_tanda_ids' => 'nullable|array',
            'deleted_tanda_ids.*' => 'integer',
        ]);

        DB::beginTransaction();
        try {
            // 2. Hapus Jadwal Lama (Logic yang sudah ada)
            Jadwal::where('hari_id', $validated['old_hari_id'])->where('sesi_id', $validated['old_sesi_id'])->where('mata_pelajaran_id', $validated['old_mapel_id'])->where('guru_id', $validated['old_guru_id'])->where('ruang_id', $validated['old_ruang_id'])->delete();

            // 3. Siapkan Data Jadwal Baru
            $newSiswaIds = array_map('intval', $validated['siswa_ids']);
            $insertData = [];
            $now = now();

            foreach ($newSiswaIds as $siswaId) {
                if ($siswaId > 0) {
                    $insertData[] = [
                        'hari_id' => $validated['old_hari_id'],
                        'sesi_id' => $validated['old_sesi_id'],
                        'mata_pelajaran_id' => $validated['mapel_id'],
                        'guru_id' => $validated['guru_id'],
                        'ruang_id' => $validated['ruang_id'],
                        'siswa_id' => $siswaId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // 4. Insert Jadwal Baru
            if (!empty($insertData)) {
                Jadwal::insert($insertData);
            }

            // 5. === [BARU] Eksekusi Hapus Tanda ===
            // Jika ada ID tanda yang dikirim untuk dihapus, hapus dari database
            if (!empty($request->deleted_tanda_ids)) {
                Tanda::whereIn('id', $request->deleted_tanda_ids)->delete();
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Jadwal dan Catatan berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // 1. Validasi Data
            $validated = $request->validate([
                'hari_id' => 'required|exists:haris,id',
                'sesi_id' => 'required|exists:sesis,id',
                'mata_pelajaran_id' => 'required|exists:mata_pelajarans,id',
                'guru_id' => 'required|exists:gurus,id',
                'ruang_id' => 'required|exists:ruangs,id',
                'siswa_ids' => 'required|array|min:1', // Harus ada minimal 1 siswa
                'siswa_ids.*' => 'exists:siswas,id', // Memastikan setiap ID siswa ada
            ]);

            // 2. Ekstrak data utama jadwal yang sama untuk semua siswa
            $jadwalDataUtama = Arr::only($validated, ['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id']);

            $siswa_ids = $validated['siswa_ids'];

            // 3. Iterasi dan Simpan Setiap Siswa
            $newJadwals = [];
            foreach ($siswa_ids as $siswa_id) {
                // Buat data lengkap untuk satu entri Jadwal
                $entriJadwal = array_merge($jadwalDataUtama, [
                    'siswa_id' => $siswa_id,
                ]);

                // Simpan ke database
                $newJadwals[] = Jadwal::create($entriJadwal);
            }

            // 4. Respon Sukses
            return response()->json([
                'status' => 'success',
                'message' => count($newJadwals) . ' jadwal baru berhasil dibuat.',
            ]);
        } catch (ValidationException $e) {
            // Tangani error validasi (e.g., ID tidak valid, siswa_ids kosong)
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Validasi gagal.',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            // Tangani error umum
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function exportPdf(Request $request)
    {
        // 1. Ambil Data Master (Untuk Header PDF)
        // Jika user filter hari tertentu, kita hanya perlu header hari itu saja (opsional, tapi biar rapi)
        if ($request->filled('hari_id')) {
            $haris = Hari::where('id', $request->hari_id)->get();
        } else {
            $haris = Hari::orderBy('id')->get();
        }

        $sesis = Sesi::orderBy('id')->get();

        // 2. Query Dasar Jadwal
        $query = Jadwal::with(['siswa.tandas', 'mataPelajaran', 'guru', 'ruang']);

        // --- LOGIKA FILTER (Sesuai Request) ---

        // Jika Siswa Dipilih (Tampilkan jadwal siswa tersebut di semua hari/hari terpilih)
        if ($request->filled('siswa_id')) {
            $query->where('siswa_id', $request->siswa_id);
        }

        // Jika Hari Dipilih (Tampilkan semua siswa/siswa terpilih di hari itu)
        if ($request->filled('hari_id')) {
            $query->where('hari_id', $request->hari_id);
        }

        $jadwalsData = $query->get();

        // 3. Grouping Data (Sama seperti sebelumnya)
        $finalJadwals = [];
        foreach ($jadwalsData as $jadwal) {
            $classKey = $jadwal->mata_pelajaran_id . '_' . $jadwal->guru_id . '_' . $jadwal->ruang_id;

            if (!isset($finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey])) {
                $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey] = [
                    'mapel' => $jadwal->mataPelajaran,
                    'guru' => $jadwal->guru,
                    'ruang' => $jadwal->ruang,
                    'siswa_list' => collect(),
                ];
            }
            $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey]['siswa_list']->push($jadwal->siswa);
        }

        // 4. Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.jadwal', [
            'haris' => $haris,
            'sesis' => $sesis,
            'jadwals' => $finalJadwals,
        ]);

        $pdf->setPaper('a4', 'landscape');

        // Beri nama file yang informatif
        $filename = 'jadwal-pelajaran';
        if ($request->filled('hari_id')) {
            $filename .= '-hari-' . $request->hari_id;
        }
        if ($request->filled('siswa_id')) {
            $filename .= '-siswa-' . $request->siswa_id;
        }

        return $pdf->download($filename . '.pdf');
    }
}
