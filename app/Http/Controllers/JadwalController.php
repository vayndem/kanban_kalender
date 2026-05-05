<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\Sesi;
use App\Models\Tanda;

class JadwalController extends Controller
{
    public function tampilKalender()
    {
        $haris = Hari::orderBy('id')->get();
        $sesis = Sesi::orderBy('start_time')->get();

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
        try {
            $validated = $request->validate([
                'old_mapel_id' => 'required|integer',
                'old_guru_id' => 'required|integer',
                'old_ruang_id' => 'required|integer',
                'old_hari_id' => 'required|integer',
                'old_sesi_id' => 'required|integer',
                'mapel_id' => 'required|integer',
                'guru_id' => 'required|integer',
                'ruang_id' => 'required|integer',
                'siswa_ids' => 'present|array',
                'siswa_ids.*' => 'integer',
                'deleted_tanda_ids' => 'nullable|array',
                'deleted_tanda_ids.*' => 'integer',
            ]);

            DB::beginTransaction();

            Jadwal::where('hari_id', $validated['old_hari_id'])
                ->where('sesi_id', $validated['old_sesi_id'])
                ->where('mata_pelajaran_id', $validated['old_mapel_id'])
                ->where('guru_id', $validated['old_guru_id'])
                ->where('ruang_id', $validated['old_ruang_id'])
                ->delete();

            if (!empty($validated['siswa_ids'])) {
                $now = now();
                $insertData = [];
                foreach ($validated['siswa_ids'] as $siswaId) {
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
                if (!empty($insertData)) {
                    Jadwal::insert($insertData);
                }
            }

            if (!empty($request->deleted_tanda_ids)) {
                Tanda::whereIn('id', $request->deleted_tanda_ids)->delete();
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Jadwal dan Catatan berhasil diperbarui.']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => implode(' ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'hari_id' => 'required|exists:haris,id',
                'sesi_id' => 'required|exists:sesis,id',
                'mata_pelajaran_id' => 'required|exists:mata_pelajarans,id',
                'guru_id' => 'required|exists:gurus,id',
                'ruang_id' => 'required|exists:ruangs,id',
                'siswa_ids' => 'required|array|min:1',
                'siswa_ids.*' => 'exists:siswas,id',
            ], [
                'required' => 'Kolom :attribute wajib diisi.',
                'siswa_ids.required' => 'Pilih minimal satu siswa.',
                'exists' => 'Data :attribute tidak valid.'
            ]);

            $jadwalDataUtama = Arr::only($validated, ['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id']);
            $newJadwals = [];

            foreach ($validated['siswa_ids'] as $siswa_id) {
                $newJadwals[] = Jadwal::create(array_merge($jadwalDataUtama, ['siswa_id' => $siswa_id]));
            }

            return response()->json([
                'status' => 'success',
                'message' => count($newJadwals) . ' jadwal baru berhasil dibuat.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => implode(' ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        $query = Jadwal::with(['siswa.tandas', 'mataPelajaran', 'guru', 'ruang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('hari', function ($h) use ($search) {
                    $h->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('sesi', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('mataPelajaran', function ($m) use ($search) {
                        $m->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('guru', function ($g) use ($search) {
                        $g->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ruang', function ($r) use ($search) {
                        $r->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('siswa', function ($st) use ($search) {
                        $st->where('name', 'like', "%{$search}%")->orWhere('panggilan', 'like', "%{$search}%");
                    });
            });
        }

        $jadwalsData = $query->get();

        $activeHariIds = $jadwalsData->pluck('hari_id')->unique()->sort()->values();
        $activeSesiIds = $jadwalsData->pluck('sesi_id')->unique()->sort()->values();

        $haris = Hari::whereIn('id', $activeHariIds)->orderBy('id')->get();
        $sesis = Sesi::whereIn('id', $activeSesiIds)->orderBy('start_time')->get();

        if ($haris->isEmpty()) $haris = Hari::orderBy('id')->get();
        if ($sesis->isEmpty()) $sesis = Sesi::orderBy('start_time')->get();

        $finalJadwals = [];
        $studentsWithNotes = collect();

        foreach ($jadwalsData as $jadwal) {
            $jadwal->siswa->formatted_name_class = $jadwal->siswa->name . ' - ' . $jadwal->siswa->kelas;
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

            if ($jadwal->siswa->tandas->isNotEmpty()) {
                if (!$studentsWithNotes->has($jadwal->siswa->id)) {
                    $studentsWithNotes->put($jadwal->siswa->id, $jadwal->siswa);
                }
            }
        }

        $pdf = Pdf::loadView('pdf.jadwal', [
            'haris' => $haris,
            'sesis' => $sesis,
            'jadwals' => $finalJadwals,
            'studentsWithNotes' => $studentsWithNotes,
            'searchQuery' => $request->search ?? null,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'jadwal-pelajaran';
        if ($request->filled('search')) {
            $filename .= '-search-' . Str::slug($request->search);
        }

        return $pdf->download($filename . '.pdf');
    }

    public function generateTextJadwal(Request $request)
    {
        $query = Jadwal::with(['siswa', 'mataPelajaran', 'guru', 'sesi', 'hari', 'ruang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('hari', function ($h) use ($search) {
                    $h->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('sesi', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('mataPelajaran', function ($m) use ($search) {
                        $m->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('guru', function ($g) use ($search) {
                        $g->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ruang', function ($r) use ($search) {
                        $r->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('siswa', function ($st) use ($search) {
                        $st->where('name', 'like', "%{$search}%")->orWhere('panggilan', 'like', "%{$search}%");
                    });
            });
        }

        $jadwals = $query->get()->sortBy([['hari_id', 'asc'], ['sesi.start_time', 'asc']]);
        $header = $request->filled('search') ? 'Filter: ' . ucwords($request->search) : 'Jadwal Lengkap';
        $textOutput = '*' . $header . "*\n\n";

        $groupedByHari = $jadwals->groupBy('hari.name');

        foreach ($groupedByHari as $hariName => $jadwalsPerHari) {
            $textOutput .= '🗓️ *' . strtoupper($hariName) . "*\n";
            $groupedBySesi = $jadwalsPerHari->groupBy('sesi.id');

            foreach ($groupedBySesi as $sesiId => $items) {
                $sesiInfo = $items->first()->sesi;
                $jamMulai = \Carbon\Carbon::parse($sesiInfo->start_time)->format('H.i');
                $jamSelesai = \Carbon\Carbon::parse($sesiInfo->end_time)->format('H.i');

                $textOutput .= "\n" . '🕰️ ' . $jamMulai . ' - ' . $jamSelesai . "\n";
                $groupedByClass = $items->groupBy(function ($item) {
                    return $item->guru->name . ' - ' . $item->mataPelajaran->name . ' - ' . $item->ruang->name;
                });

                foreach ($groupedByClass as $key => $classItems) {
                    $guruName = $classItems->first()->guru->name;
                    $ruangName = $classItems->first()->ruang->name;
                    $mataPelajaranName = $classItems->first()->mataPelajaran->name;

                    $studentDetails = $classItems->map(function ($j) {
                        $displayName = $j->siswa->panggilan ?? explode(' ', trim($j->siswa->name))[0];
                        return $displayName . ' - ' . $j->siswa->kelas;
                    })->implode(', ');

                    $textOutput .= "\n";
                    $textOutput .= '📚 *' . $mataPelajaranName . "*\n";
                    $textOutput .= '👩‍🏫 Guru: ' . $guruName . "\n";
                    $textOutput .= '🏠 Ruang: ' . $ruangName . "\n";
                    $textOutput .= '🧑‍🎓 Siswa: ' . $studentDetails . "\n";
                }
            }
        }

        return response()->json(['status' => 'success', 'text' => $textOutput]);
    }
}
