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
use Illuminate\Support\Facades\Response;

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
        $validated = $request->validate([
            'mapel_id' => 'required|exists:mata_pelajarans,id',
            'guru_id' => 'required|exists:gurus,id',
            'ruang_id' => 'required|exists:ruangs,id',
            'old_hari_id' => 'required|exists:haris,id',
            'old_sesi_id' => 'required|exists:sesis,id',
            'new_hari_id' => 'required|exists:haris,id',
            'new_sesi_id' => 'required|exists:sesis,id',
        ]);

        try {
            $source = Jadwal::where('mata_pelajaran_id', $validated['mapel_id'])
                ->where('guru_id', $validated['guru_id'])
                ->where('ruang_id', $validated['ruang_id'])
                ->where('hari_id', $validated['old_hari_id'])
                ->where('sesi_id', $validated['old_sesi_id']);
            $studentIds = (clone $source)->pluck('siswa_id')->all();

            if ($studentIds === []) {
                return response()->json(['status' => 'warning', 'message' => 'Jadwal asal tidak ditemukan.']);
            }

            $this->ensureNoConflicts(
                $validated['new_hari_id'], $validated['new_sesi_id'],
                $validated['mapel_id'], $validated['guru_id'], $validated['ruang_id'],
                $studentIds,
                Arr::only($validated, ['old_hari_id', 'old_sesi_id', 'mapel_id', 'guru_id', 'ruang_id'])
            );

            $affectedRows = DB::transaction(fn () => $source->update([
                'hari_id' => $validated['new_hari_id'],
                'sesi_id' => $validated['new_sesi_id'],
                'updated_at' => now(),
            ]));

            if ($affectedRows > 0) {
                return response()->json(['status' => 'success', 'message' => 'Jadwal berhasil dipindahkan.']);
            } else {
                return response()->json(['status' => 'warning', 'message' => 'Tidak ada jadwal yang dipindahkan.'], 200);
            }
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => implode(' ', $e->validator->errors()->all())], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateKelas(Request $request)
    {
        try {
            $validated = $request->validate([
                'old_mapel_id' => 'required|exists:mata_pelajarans,id',
                'old_guru_id' => 'required|exists:gurus,id',
                'old_ruang_id' => 'required|exists:ruangs,id',
                'old_hari_id' => 'required|exists:haris,id',
                'old_sesi_id' => 'required|exists:sesis,id',
                'mapel_id' => 'required|exists:mata_pelajarans,id',
                'guru_id' => 'required|exists:gurus,id',
                'ruang_id' => 'required|exists:ruangs,id',
                'siswa_ids' => 'present|array',
                'siswa_ids.*' => 'distinct|exists:siswas,id',
                'deleted_tanda_ids' => 'nullable|array',
                'deleted_tanda_ids.*' => 'integer',
            ]);

            $this->ensureNoConflicts(
                $validated['old_hari_id'], $validated['old_sesi_id'],
                $validated['mapel_id'], $validated['guru_id'], $validated['ruang_id'],
                $validated['siswa_ids'],
                [
                    'old_hari_id' => $validated['old_hari_id'], 'old_sesi_id' => $validated['old_sesi_id'],
                    'mapel_id' => $validated['old_mapel_id'], 'guru_id' => $validated['old_guru_id'],
                    'ruang_id' => $validated['old_ruang_id'],
                ]
            );

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
                'siswa_ids.*' => 'distinct|exists:siswas,id',
            ], [
                'required' => 'Kolom :attribute wajib diisi.',
                'siswa_ids.required' => 'Pilih minimal satu siswa.',
                'exists' => 'Data :attribute tidak valid.'
            ]);

            $jadwalDataUtama = Arr::only($validated, ['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id']);
            $this->ensureNoConflicts(
                $validated['hari_id'], $validated['sesi_id'], $validated['mata_pelajaran_id'],
                $validated['guru_id'], $validated['ruang_id'], $validated['siswa_ids']
            );

            $createdCount = DB::transaction(function () use ($jadwalDataUtama, $validated) {
                foreach ($validated['siswa_ids'] as $siswaId) {
                    Jadwal::create(array_merge($jadwalDataUtama, ['siswa_id' => $siswaId]));
                }
                return count($validated['siswa_ids']);
            });

            return response()->json([
                'status' => 'success',
                'message' => $createdCount . ' jadwal baru berhasil dibuat.',
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

    private function ensureNoConflicts(
        int $hariId,
        int $sesiId,
        int $mapelId,
        int $guruId,
        int $ruangId,
        array $studentIds,
        ?array $excludeClass = null
    ): void {
        $query = Jadwal::where('hari_id', $hariId)->where('sesi_id', $sesiId);

        if ($excludeClass) {
            $query->where(function ($q) use ($excludeClass) {
                $q->where('hari_id', '!=', $excludeClass['old_hari_id'])
                    ->orWhere('sesi_id', '!=', $excludeClass['old_sesi_id'])
                    ->orWhere('mata_pelajaran_id', '!=', $excludeClass['mapel_id'])
                    ->orWhere('guru_id', '!=', $excludeClass['guru_id'])
                    ->orWhere('ruang_id', '!=', $excludeClass['ruang_id']);
            });
        }

        $conflicts = [];
        if ((clone $query)->where('guru_id', $guruId)->exists()) {
            $conflicts[] = 'Guru sudah mengajar pada hari dan sesi tersebut.';
        }
        if ((clone $query)->where('ruang_id', $ruangId)->exists()) {
            $conflicts[] = 'Ruang sudah digunakan pada hari dan sesi tersebut.';
        }
        if ($studentIds !== [] && (clone $query)->whereIn('siswa_id', $studentIds)->exists()) {
            $conflicts[] = 'Satu atau lebih siswa sudah memiliki jadwal pada waktu tersebut.';
        }

        if ($conflicts !== []) {
            throw ValidationException::withMessages(['jadwal' => $conflicts]);
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

        return response()->json([
            'status' => 'success',
            'text' => $this->buildWhatsappScheduleText($query->get(), $request->string('search')->toString()),
        ]);

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

    private function buildWhatsappScheduleText($jadwals, string $search): string
    {
        $jadwals = $jadwals->sortBy([['hari_id', 'asc'], ['sesi.start_time', 'asc']]);
        $text = "*JADWAL E-LING COURSE*\n";
        if ($search !== '') {
            $text .= '_Jadwal untuk: ' . ucwords($search) . "_\n";
        }
        $text .= '_Dibuat ' . now()->translatedFormat('d F Y, H:i') . "_\n";

        if ($jadwals->isEmpty()) {
            return $text . "\nTidak ada jadwal yang ditemukan.";
        }

        foreach ($jadwals->groupBy('hari.name') as $hariName => $daySchedules) {
            $text .= "\n━━━━━━━━━━━━━━\n📅 *" . mb_strtoupper((string) $hariName) . "*\n";

            foreach ($daySchedules->groupBy('sesi.id') as $items) {
                $session = $items->first()->sesi;
                $start = \Carbon\Carbon::parse($session->start_time)->format('H.i');
                $end = \Carbon\Carbon::parse($session->end_time)->format('H.i');

                $classes = $items->groupBy(fn ($item) => implode('-', [
                    $item->guru_id, $item->mata_pelajaran_id, $item->ruang_id,
                ]));

                foreach ($classes as $classItems) {
                    $schedule = $classItems->first();
                    $students = $classItems->map(function ($item) {
                        $name = $item->siswa->panggilan ?: explode(' ', trim($item->siswa->name))[0];
                        return $name . ($item->siswa->kelas ? ' – ' . $item->siswa->kelas : '');
                    })->unique()->join(', ');

                    $text .= "\n⏰ *{$start}–{$end}* · *{$schedule->mataPelajaran->name}*\n";
                    $text .= "   👩‍🏫 {$schedule->guru->name}\n";
                    $text .= "   🏫 {$schedule->ruang->name}\n";
                    $text .= "   👥 {$students}\n";
                }
            }
        }

        return $text . "\n━━━━━━━━━━━━━━\n_Simpan pesan ini sebagai pengingat jadwal._";
    }

    public function downloadStash()
    {
        // Ambil seluruh data jadwal mentah tanpa filter
        $allJadwals = Jadwal::all()->map(function ($j) {
            return [
                'h' => $j->hari_id,
                's' => $j->sesi_id,
                'm' => $j->mata_pelajaran_id,
                'g' => $j->guru_id,
                'r' => $j->ruang_id,
                'si' => $j->siswa_id,
            ];
        });

        $data = [
            'app' => 'E-Ling-Course',
            'version' => '1.0',
            'timestamp' => now()->toDateTimeString(),
            'content' => $allJadwals
        ];

        // Encode ke Base64 agar user tidak bisa baca langsung isinya
        $encodedData = base64_encode(json_encode($data));
        $filename = "JADWAL_STASH_" . date('Ymd_His') . ".stash";

        return Response::make($encodedData, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }

    public function uploadStash(Request $request)
    {
        $request->validate([
            'file_stash' => 'required|file'
        ]);

        try {
            $fileContent = file_get_contents($request->file('file_stash')->getRealPath());
            $decodedData = json_decode(base64_decode($fileContent), true);

            if (!$decodedData || !isset($decodedData['app']) || $decodedData['app'] !== 'E-Ling-Course') {
                return response()->json(['status' => 'error', 'message' => 'Format file stash tidak dikenali!'], 422);
            }

            $incomingJadwals = $decodedData['content'];

            DB::beginTransaction();
            Jadwal::query()->delete();

            $insertData = [];
            $now = now();
            foreach ($incomingJadwals as $j) {
                $insertData[] = [
                    'hari_id'           => $j['h'],
                    'sesi_id'           => $j['s'],
                    'mata_pelajaran_id' => $j['m'],
                    'guru_id'           => $j['g'],
                    'ruang_id'          => $j['r'],
                    'siswa_id'          => $j['si'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }

            // Chunk insert untuk performa
            foreach (array_chunk($insertData, 500) as $chunk) {
                Jadwal::insert($chunk);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Seluruh jadwal berhasil direplace dengan data stash!'
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json(['status' => 'error', 'message' => 'Gagal upload: ' . $e->getMessage()], 500);
        }
    }
}
