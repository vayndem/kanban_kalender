<?php

namespace App\Http\Controllers;

use App\Exports\JadwalExport;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\JadwalTeksLog;
use App\Models\Sesi;
use App\Models\StashPemulihanLog;
use App\Models\Tanda;
use App\Services\IrisanSesiService;
use App\Services\StashJadwalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class JadwalController extends Controller
{
    public function tampilKalender()
    {
        $haris = Hari::query()->select(['id', 'name'])->orderBy('id')->get();
        $sesis = Sesi::query()->select(['id', 'name', 'start_time', 'end_time'])->orderBy('start_time')->get();

        $jadwalsData = Jadwal::query()
            ->select(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
            ->with([
                'siswa:id,name,panggilan,kelas',
                'mataPelajaran:id,name',
                'guru:id,name',
                'ruang:id,name',
            ])
            ->get();

        $finalJadwals = [];
        foreach ($jadwalsData as $jadwal) {
            $classKey = $jadwal->mata_pelajaran_id.'_'.$jadwal->guru_id.'_'.$jadwal->ruang_id;

            if (! isset($finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey])) {
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
            'mapels' => $jadwalsData->pluck('mataPelajaran')->filter()->unique('id')->sortBy('name')->values(),
            'gurus' => $jadwalsData->pluck('guru')->filter()->unique('id')->sortBy('name')->values(),
            'ruangs' => $jadwalsData->pluck('ruang')->filter()->unique('id')->sortBy('name')->values(),
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
                $validated['new_hari_id'],
                $validated['new_sesi_id'],
                $validated['mapel_id'],
                $validated['guru_id'],
                $validated['ruang_id'],
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
        } catch (\Throwable $e) {
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
                $validated['old_hari_id'],
                $validated['old_sesi_id'],
                $validated['mapel_id'],
                $validated['guru_id'],
                $validated['ruang_id'],
                $validated['siswa_ids'],
                [
                    'old_hari_id' => $validated['old_hari_id'],
                    'old_sesi_id' => $validated['old_sesi_id'],
                    'mapel_id' => $validated['old_mapel_id'],
                    'guru_id' => $validated['old_guru_id'],
                    'ruang_id' => $validated['old_ruang_id'],
                ]
            );

            DB::beginTransaction();

            $kelasLama = Jadwal::where('hari_id', $validated['old_hari_id'])
                ->where('sesi_id', $validated['old_sesi_id'])
                ->where('mata_pelajaran_id', $validated['old_mapel_id'])
                ->where('guru_id', $validated['old_guru_id'])
                ->where('ruang_id', $validated['old_ruang_id']);
            $kodeKelas = (clone $kelasLama)->value('kode_kelas') ?: (string) Str::uuid();
            $kelasLama->delete();

            if (! empty($validated['siswa_ids'])) {
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
                            'kode_kelas' => $kodeKelas,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                if (! empty($insertData)) {
                    Jadwal::insert($insertData);
                }
            }

            if (! empty($request->deleted_tanda_ids)) {
                Tanda::whereIn('id', $request->deleted_tanda_ids)->delete();
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Jadwal dan Catatan berhasil diperbarui.']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => implode(' ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: '.$e->getMessage()], 500);
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
                'exists' => 'Data :attribute tidak valid.',
            ]);

            $jadwalDataUtama = Arr::only($validated, ['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id']);
            $jadwalDataUtama['kode_kelas'] = (string) Str::uuid();
            $this->ensureNoConflicts(
                $validated['hari_id'],
                $validated['sesi_id'],
                $validated['mata_pelajaran_id'],
                $validated['guru_id'],
                $validated['ruang_id'],
                $validated['siswa_ids']
            );

            $createdCount = DB::transaction(function () use ($jadwalDataUtama, $validated) {
                $now = now();
                $rows = collect($validated['siswa_ids'])
                    ->map(fn ($studentId) => array_merge($jadwalDataUtama, [
                        'siswa_id' => $studentId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]))
                    ->all();

                Jadwal::query()->insert($rows);

                return count($rows);
            });

            return response()->json([
                'status' => 'success',
                'message' => $createdCount.' jadwal baru berhasil dibuat.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => implode(' ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan jadwal: '.$e->getMessage(),
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
        $sesiBentrok = app(IrisanSesiService::class)->idBeririsan($sesiId);

        $query = Jadwal::where('hari_id', $hariId)->whereIn('sesi_id', $sesiBentrok);

        if ($excludeClass) {
            $query->where(function ($q) use ($excludeClass) {
                $q->where('hari_id', '!=', $excludeClass['old_hari_id'])
                    ->orWhere('sesi_id', '!=', $excludeClass['old_sesi_id'])
                    ->orWhere('mata_pelajaran_id', '!=', $excludeClass['mapel_id'])
                    ->orWhere('guru_id', '!=', $excludeClass['guru_id'])
                    ->orWhere('ruang_id', '!=', $excludeClass['ruang_id']);
            });
        }

        $studentIds = array_map('intval', $studentIds);
        $occupied = $query
            ->where(function ($conflictQuery) use ($guruId, $ruangId, $studentIds) {
                $conflictQuery->where('guru_id', $guruId)
                    ->orWhere('ruang_id', $ruangId);

                if ($studentIds !== []) {
                    $conflictQuery->orWhereIn('siswa_id', $studentIds);
                }
            })
            ->with('sesi:id,name,start_time,end_time')
            ->get(['guru_id', 'ruang_id', 'siswa_id', 'sesi_id']);

        $sebut = function ($baris) use ($sesiId) {
            $sesi = $baris?->sesi;
            if (! $sesi) {
                return 'sesi tersebut';
            }

            return $sesi->id === $sesiId
                ? 'sesi '.$sesi->name
                : 'sesi '.$sesi->label.' yang jamnya bertindih';
        };

        $conflicts = [];
        if (($bentrokGuru = $occupied->firstWhere('guru_id', $guruId)) !== null) {
            $conflicts[] = 'Guru sudah mengajar pada '.$sebut($bentrokGuru).'.';
        }
        if (($bentrokRuang = $occupied->firstWhere('ruang_id', $ruangId)) !== null) {
            $conflicts[] = 'Ruang sudah digunakan pada '.$sebut($bentrokRuang).'.';
        }
        if ($studentIds !== []) {
            $bentrokSiswa = $occupied->first(fn ($b) => in_array((int) $b->siswa_id, $studentIds, true));
            if ($bentrokSiswa !== null) {
                $conflicts[] = 'Satu atau lebih siswa sudah punya jadwal pada '.$sebut($bentrokSiswa).'.';
            }
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

        if ($haris->isEmpty()) {
            $haris = Hari::orderBy('id')->get();
        }
        if ($sesis->isEmpty()) {
            $sesis = Sesi::orderBy('start_time')->get();
        }

        $finalJadwals = [];
        $studentsWithNotes = collect();
        $bolehLihatCatatan = auth()->check() && auth()->user()->hasRole('admin');

        foreach ($jadwalsData as $jadwal) {
            $jadwal->siswa->formatted_name_class = $jadwal->siswa->name.' - '.$jadwal->siswa->kelas;
            $classKey = $jadwal->mata_pelajaran_id.'_'.$jadwal->guru_id.'_'.$jadwal->ruang_id;

            if (! isset($finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey])) {
                $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey] = [
                    'mapel' => $jadwal->mataPelajaran,
                    'guru' => $jadwal->guru,
                    'ruang' => $jadwal->ruang,
                    'siswa_list' => collect(),
                ];
            }
            $finalJadwals[$jadwal->hari_id][$jadwal->sesi_id][$classKey]['siswa_list']->push($jadwal->siswa);

            if ($bolehLihatCatatan && $jadwal->siswa->tandas->isNotEmpty()) {
                if (! $studentsWithNotes->has($jadwal->siswa->id)) {
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
            $filename .= '-search-'.Str::slug($request->search);
        }

        return $pdf->download($filename.'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $query = Jadwal::with(['siswa.tandas', 'mataPelajaran', 'guru', 'ruang', 'hari', 'sesi']);

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

        $jadwals = $query->get();

        $filename = 'jadwal-pelajaran';
        if ($request->filled('search')) {
            $filename .= '-search-'.Str::slug($request->search);
        }

        return Excel::download(new JadwalExport($jadwals, $request->search), $filename.'.xlsx');
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

        JadwalTeksLog::create(['user_id' => $request->user()?->id]);

        return response()->json([
            'status' => 'success',
            'text' => $this->buildWhatsappScheduleText($query->get(), $request->string('search')->toString()),
        ]);

        $jadwals = $query->get()->sortBy([['hari_id', 'asc'], ['sesi.start_time', 'asc']]);
        $header = $request->filled('search') ? 'Filter: '.ucwords($request->search) : 'Jadwal Lengkap';
        $textOutput = '*'.$header."*\n\n";

        $groupedByHari = $jadwals->groupBy('hari.name');

        foreach ($groupedByHari as $hariName => $jadwalsPerHari) {
            $textOutput .= '🗓️ *'.strtoupper($hariName)."*\n";
            $groupedBySesi = $jadwalsPerHari->groupBy('sesi.id');

            foreach ($groupedBySesi as $sesiId => $items) {
                $sesiInfo = $items->first()->sesi;
                $jamMulai = Carbon::parse($sesiInfo->start_time)->format('H.i');
                $jamSelesai = Carbon::parse($sesiInfo->end_time)->format('H.i');

                $textOutput .= "\n".'🕰️ '.$jamMulai.' - '.$jamSelesai."\n";
                $groupedByClass = $items->groupBy(function ($item) {
                    return $item->guru->name.' - '.$item->mataPelajaran->name.' - '.$item->ruang->name;
                });

                foreach ($groupedByClass as $key => $classItems) {
                    $guruName = $classItems->first()->guru->name;
                    $ruangName = $classItems->first()->ruang->name;
                    $mataPelajaranName = $classItems->first()->mataPelajaran->name;

                    $studentDetails = $classItems->map(function ($j) {
                        $displayName = $j->siswa->panggilan ?? explode(' ', trim($j->siswa->name))[0];

                        return $displayName.' - '.$j->siswa->kelas;
                    })->implode(', ');

                    $textOutput .= "\n";
                    $textOutput .= '📚 *'.$mataPelajaranName."*\n";
                    $textOutput .= '👩‍🏫 Guru: '.$guruName."\n";
                    $textOutput .= '🏠 Ruang: '.$ruangName."\n";
                    $textOutput .= '🧑‍🎓 Siswa: '.$studentDetails."\n";
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
            $text .= '_Jadwal untuk: '.ucwords($search)."_\n";
        }
        $text .= '_Dibuat '.now()->translatedFormat('d F Y, H:i')."_\n";

        if ($jadwals->isEmpty()) {
            return $text."\nTidak ada jadwal yang ditemukan.";
        }

        foreach ($jadwals->groupBy('hari.name') as $hariName => $daySchedules) {
            $text .= "\n━━━━━━━━━━━━━━\n📅 *".mb_strtoupper((string) $hariName)."*\n";

            foreach ($daySchedules->groupBy('sesi.id') as $items) {
                $session = $items->first()->sesi;
                $start = Carbon::parse($session->start_time)->format('H.i');
                $end = Carbon::parse($session->end_time)->format('H.i');

                $classes = $items->groupBy(fn ($item) => implode('-', [
                    $item->guru_id,
                    $item->mata_pelajaran_id,
                    $item->ruang_id,
                ]));

                foreach ($classes as $classItems) {
                    $schedule = $classItems->first();
                    $students = $classItems->map(function ($item) {
                        $name = $item->siswa->panggilan ?: explode(' ', trim($item->siswa->name))[0];

                        return $name.($item->siswa->kelas ? ' – '.$item->siswa->kelas : '');
                    })->unique()->join(', ');

                    $text .= "\n⏰ *{$start}–{$end}* · *{$schedule->mataPelajaran->name}*\n";
                    $text .= "   👩‍🏫 {$schedule->guru->name}\n";
                    $text .= "   🏫 {$schedule->ruang->name}\n";
                    $text .= "   👥 {$students}\n";
                }
            }
        }

        return $text."\n━━━━━━━━━━━━━━\n_Simpan pesan ini sebagai pengingat jadwal._";
    }

    public function downloadStash(StashJadwalService $stash)
    {
        $data = $stash->bungkus();
        $filename = 'JADWAL_STASH_'.date('Ymd_His').'.stash';

        return Response::make($stash->encode($data), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ]);
    }

    public function uploadStash(Request $request, StashJadwalService $stash)
    {
        $request->validate([
            'file_stash' => 'required|file|max:10240',
        ], [
            'file_stash.required' => 'Pilih dulu file stash yang mau dipulihkan.',
            'file_stash.max' => 'File stash terlalu besar (maksimal 10 MB).',
        ]);

        try {
            $baris = $stash->baca(file_get_contents($request->file('file_stash')->getRealPath()));
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        try {
            $log = $stash->pulihkan($baris, $request->user(), fn () => $this->isiKodeKelasKosong());
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memulihkan stash. Jadwal lama tidak diubah.',
            ], 500);
        }

        $pesan = "Jadwal dipulihkan: {$log->jumlah_sebelum} baris diganti dengan {$log->jumlah_sesudah} baris.";
        if ($log->bentrok_masuk > 0) {
            $pesan .= " Perhatian: {$log->bentrok_masuk} bentrok ikut masuk, cek panel Bentrok Tersembunyi di Ringkasan.";
        }
        $pesan .= ' Kondisi sebelumnya tersimpan dan bisa diunduh untuk dikembalikan.';

        return response()->json([
            'status' => 'success',
            'message' => $pesan,
            'pemulihan_id' => $log->id,
            'unduh_kondisi_sebelumnya' => route('admin.jadwal.unduhCadanganStash', $log->id),
        ]);
    }

    public function unduhCadanganStash(StashPemulihanLog $pemulihan)
    {
        $filename = 'SEBELUM_PEMULIHAN_'.$pemulihan->id.'_'.$pemulihan->created_at->format('Ymd_His').'.stash';

        return Response::make($pemulihan->isi_sebelum, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ]);
    }

    private function isiKodeKelasKosong(): void
    {
        Jadwal::query()
            ->whereNull('kode_kelas')
            ->select(['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id'])
            ->distinct()
            ->get()
            ->each(function ($kelompok) {
                Jadwal::whereNull('kode_kelas')
                    ->where('hari_id', $kelompok->hari_id)
                    ->where('sesi_id', $kelompok->sesi_id)
                    ->where('mata_pelajaran_id', $kelompok->mata_pelajaran_id)
                    ->where('guru_id', $kelompok->guru_id)
                    ->where('ruang_id', $kelompok->ruang_id)
                    ->update(['kode_kelas' => (string) Str::uuid()]);
            });
    }
}
