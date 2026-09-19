<?php

namespace App\Services;

use App\Models\Arsip;
use App\Models\Diskon;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\JadwalTeksLog;
use App\Models\MataPelajaran;
use App\Models\ModulAjarDetail;
use App\Models\Pembayaran;
use App\Models\Pertemuan;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\Tanda;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RingkasanService
{
    private const BACK_TO_BACK_THRESHOLD = 3;

    private const TANDA_LAMA_HARI = 14;

    public const KELAS_SEPI_MINIMAL = 3;

    public function __construct(private readonly IrisanSesiService $irisanSesi) {}

    public function ringkasanHariIni(): array
    {
        $dayOfWeek = Hari::idHariIni();

        $jadwalHariIni = Jadwal::query()
            ->select(['sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
            ->where('hari_id', $dayOfWeek)
            ->get();

        $classKeys = $jadwalHariIni->map(fn (Jadwal $j) => "{$j->sesi_id}_{$j->mata_pelajaran_id}_{$j->guru_id}_{$j->ruang_id}")->unique();

        return [
            'kelas_aktif' => $classKeys->count(),
            'siswa_terjadwal' => $jadwalHariIni->pluck('siswa_id')->unique()->count(),
            'guru_mengajar' => $jadwalHariIni->pluck('guru_id')->unique()->count(),
            'ruang_terpakai' => $jadwalHariIni->pluck('ruang_id')->unique()->count(),
            'total_ruang' => Ruang::count(),
        ];
    }

    public function kelasHariIni(): Collection
    {
        $dayOfWeek = Hari::idHariIni();

        $jadwals = Jadwal::query()
            ->select(['id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
            ->where('hari_id', $dayOfWeek)
            ->with([
                'sesi:id,name,start_time,end_time',
                'mataPelajaran:id,name',
                'guru:id,name',
                'ruang:id,name',
                'siswa:id,name,panggilan,kelas',
            ])
            ->get();

        return $jadwals
            ->groupBy(fn (Jadwal $j) => "{$j->sesi_id}_{$j->mata_pelajaran_id}_{$j->guru_id}_{$j->ruang_id}")
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'sesi_id' => $first->sesi_id,
                    'sesi_name' => $first->sesi?->name ?? 'N/A',
                    'sesi_label' => $first->sesi?->label ?? 'N/A',
                    'sesi_start' => $first->sesi?->start_time,
                    'sesi_end' => $first->sesi?->end_time,
                    'mapel_name' => $first->mataPelajaran?->name ?? 'N/A',
                    'guru_name' => $first->guru?->name ?? 'N/A',
                    'ruang_name' => $first->ruang?->name ?? 'N/A',
                    'siswa_list' => $rows->pluck('siswa')->filter()->map(fn (Siswa $s) => [
                        'name' => $s->name,
                        'panggilan' => $s->panggilan,
                        'kelas' => $s->kelas,
                    ])->values(),
                ];
            })
            ->sortBy(fn ($card) => $card['sesi_start'] ?? '')
            ->values();
    }

    public function okupansiRuang(string $periode = 'mingguan'): array
    {
        $isHarian = $periode === 'harian';

        $kapasitasHarian = $this->irisanSesi->kapasitasSlotPerHari();
        $totalSlot = $isHarian ? $kapasitasHarian : Hari::count() * $kapasitasHarian;

        $query = Jadwal::query()->select(['ruang_id', 'hari_id', 'sesi_id']);
        if ($isHarian) {
            $query->where('hari_id', Hari::idHariIni());
        }

        $jadwals = $query->get();
        $terpakaiByRuang = $jadwals->groupBy('ruang_id')->map(
            fn (Collection $rows) => $rows->unique(fn (Jadwal $r) => "{$r->hari_id}_{$r->sesi_id}")->count()
        );

        $data = Ruang::orderBy('name')->get(['id', 'name'])->map(function (Ruang $ruang) use ($terpakaiByRuang, $totalSlot) {
            $terpakai = $terpakaiByRuang->get($ruang->id, 0);

            return [
                'id' => $ruang->id,
                'name' => $ruang->name,
                'terpakai' => $terpakai,
                'total_slot' => $totalSlot,
                'persentase' => $totalSlot > 0 ? min(100.0, round($terpakai / $totalSlot * 100, 1)) : 0,
            ];
        })->sortByDesc('persentase')->values();

        $rataRata = round((float) $data->avg('persentase'), 1);

        $ramai = $rataRata > 0
            ? $data->filter(fn ($r) => $r['persentase'] > $rataRata * 1.5)->values()
            : collect();
        $sepi = $data->filter(fn ($r) => $r['terpakai'] === 0 || ($rataRata > 0 && $r['persentase'] < $rataRata * 0.5))->values();

        return [
            'data' => $data,
            'rata_rata' => $rataRata,
            'ramai' => $ramai,
            'sepi' => $sepi,
            'periode' => $periode,
        ];
    }

    public function bebanGuru(string $periode = 'mingguan'): array
    {
        $isHarian = $periode === 'harian';

        $sesis = Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']);
        $urutanSesi = $sesis->values()->mapWithKeys(fn (Sesi $s, int $i) => [$s->id => $i]);
        $sesiById = $sesis->keyBy('id');
        $durasiSesi = $sesis->mapWithKeys(
            fn (Sesi $s) => [$s->id => (int) Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time))]
        );

        $query = Jadwal::query()->select(['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id']);
        if ($isHarian) {
            $query->where('hari_id', Hari::idHariIni());
        }

        $jadwals = $query->get()
            ->unique(fn (Jadwal $j) => "{$j->hari_id}_{$j->sesi_id}_{$j->mata_pelajaran_id}_{$j->guru_id}_{$j->ruang_id}");

        $namaGuru = Guru::pluck('name', 'id');
        $namaHari = Hari::pluck('name', 'id');

        $bebanPerGuru = $jadwals->groupBy('guru_id')->map(function (Collection $rows, $guruId) use ($durasiSesi, $namaGuru) {
            return [
                'guru_id' => $guruId,
                'nama' => $namaGuru->get($guruId, 'N/A'),
                'jumlah_sesi' => $rows->count(),
                'total_menit' => $rows->sum(fn (Jadwal $r) => $durasiSesi->get($r->sesi_id, 0)),
            ];
        })->sortByDesc('total_menit')->values();

        $backToBack = collect();
        foreach ($jadwals->groupBy('guru_id') as $guruId => $rowsPerGuru) {
            foreach ($rowsPerGuru->groupBy('hari_id') as $hariId => $rowsPerHari) {
                $urutanTerpakai = $rowsPerHari
                    ->pluck('sesi_id')
                    ->unique()
                    ->map(fn ($sesiId) => ['sesi_id' => $sesiId, 'sesi' => $sesiById->get($sesiId)])
                    ->filter(fn ($item) => $item['sesi'] !== null)
                    ->sortBy(fn ($item) => $item['sesi']->start_time)
                    ->values();

                $runSaatIni = collect();
                $runTerpanjang = collect();
                $selesaiSebelumnya = null;
                foreach ($urutanTerpakai as $item) {
                    $menyambung = $selesaiSebelumnya !== null
                        && $item['sesi']->start_time <= $selesaiSebelumnya;

                    $runSaatIni = $menyambung ? $runSaatIni->push($item) : collect([$item]);
                    if ($runSaatIni->count() > $runTerpanjang->count()) {
                        $runTerpanjang = $runSaatIni;
                    }
                    $selesaiSebelumnya = $item['sesi']->end_time;
                }

                if ($runTerpanjang->count() >= self::BACK_TO_BACK_THRESHOLD) {
                    $backToBack->push([
                        'guru_id' => $guruId,
                        'nama' => $namaGuru->get($guruId, 'N/A'),
                        'hari' => $namaHari->get($hariId, 'N/A'),
                        'jumlah_beruntun' => $runTerpanjang->count(),
                        'sesi_list' => $runTerpanjang->map(function ($item) use ($sesiById) {
                            $sesi = $sesiById->get($item['sesi_id']);

                            return [
                                'name' => $sesi?->name ?? 'N/A',
                                'start' => $sesi ? substr((string) $sesi->start_time, 0, 5) : '',
                                'end' => $sesi ? substr((string) $sesi->end_time, 0, 5) : '',
                            ];
                        })->values(),
                    ]);
                }
            }
        }

        return [
            'beban' => $bebanPerGuru,
            'back_to_back' => $backToBack->sortByDesc('jumlah_beruntun')->values(),
            'ambang_beruntun' => self::BACK_TO_BACK_THRESHOLD,
            'periode' => $periode,
        ];
    }

    public function pengingatFinansial(PaymentBatchService $paymentBatchService, int $piutangBulan): array
    {
        $cutoff = Carbon::now()->subMonths($piutangBulan);

        $piutangLama = Pembayaran::query()
            ->select(['id', 'id_siswa', 'no_hp', 'harga', 'total_sudah_dibayar', 'created_at'])
            ->with('siswa:id,name')
            ->whereIn('status', [0, 1])
            ->where('created_at', '<=', $cutoff)
            ->orderBy('created_at')
            ->get()
            ->groupBy('no_hp')
            ->map(function (Collection $rows, $noHp) {
                return [
                    'no_hp' => $noHp,
                    'siswa_names' => $rows->pluck('siswa.name')->filter()->unique()->implode(', '),
                    'total_sisa' => $rows->sum(fn (Pembayaran $r) => max(0, (int) $r->harga - (int) $r->total_sudah_dibayar)),
                    'jumlah_invoice' => $rows->count(),
                    'invoice_tertua' => $rows->min('created_at'),
                ];
            })
            ->sortByDesc('total_sisa')
            ->values();

        $activePhones = Pembayaran::whereNotNull('no_hp')->distinct()->pluck('no_hp');
        $diskonMenggantung = Diskon::whereNotNull('no_hp')
            ->whereNotIn('no_hp', $activePhones)
            ->get(['id', 'no_hp', 'diskon', 'keterangan']);

        return [
            'belum_ditagih' => $paymentBatchService->previewMissingInvoices(),
            'piutang_lama' => $piutangLama,
            'piutang_bulan' => $piutangBulan,
            'diskon_menggantung' => $diskonMenggantung,
        ];
    }

    public function kebersihanData(int $arsipBulan = 3): array
    {
        return [
            'siswa_tanpa_jadwal' => Siswa::doesntHave('jadwals')->get(['id', 'name', 'kelas', 'no_hp']),
            'siswa_tanpa_hp' => Siswa::where(fn ($q) => $q->whereNull('no_hp')->orWhere('no_hp', ''))->get(['id', 'name', 'kelas']),
            'guru_tidak_terpakai' => Guru::doesntHave('jadwals')->get(['id', 'name']),
            'ruang_tidak_terpakai' => Ruang::doesntHave('jadwals')->get(['id', 'name']),
            'mapel_tidak_terpakai' => MataPelajaran::doesntHave('jadwals')->get(['id', 'name']),
            'arsip_mengendap' => Arsip::where('created_at', '<=', now()->subMonths($arsipBulan))->get(['id', 'name', 'kelas', 'created_at']),
            'arsip_bulan' => $arsipBulan,
            'tanda_lama' => Tanda::where('created_at', '<=', now()->subDays(self::TANDA_LAMA_HARI))
                ->with('siswa:id,name,kelas')
                ->orderBy('created_at')
                ->get(['id', 'siswa_id', 'keterangan', 'created_at']),
            'tanda_lama_hari' => self::TANDA_LAMA_HARI,
            'kelas_sepi' => $this->kelasSepi(),
            'kelas_sepi_minimal' => self::KELAS_SEPI_MINIMAL,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function kelasSepi(): Collection
    {
        $jumlahPerKelas = Jadwal::query()
            ->whereNotNull('kode_kelas')
            ->where('kode_kelas', '!=', '')
            ->get(['kode_kelas', 'siswa_id'])
            ->groupBy('kode_kelas')
            ->map(fn (Collection $rows) => $rows->pluck('siswa_id')->filter()->unique()->count())
            ->filter(fn (int $jumlah) => $jumlah < self::KELAS_SEPI_MINIMAL);

        if ($jumlahPerKelas->isEmpty()) {
            return collect();
        }

        $kelas = $this->petaKelasRingkas($jumlahPerKelas->keys());

        return $jumlahPerKelas
            ->map(fn (int $jumlah, string $kode) => array_merge(
                $kelas[$kode] ?? $this->kelasTidakDikenal(),
                ['kode_kelas' => $kode, 'jumlah_siswa' => $jumlah, 'kurang' => self::KELAS_SEPI_MINIMAL - $jumlah]
            ))
            ->sortBy([['jumlah_siswa', 'asc'], ['hari', 'asc']])
            ->values();
    }

    public function kelasPengganti(): array
    {
        $terbuka = ModulAjarDetail::where('tidak_bisa_hadir', true)
            ->with('modulAjar:id,kode_kelas')
            ->withCount(['pertemuans as pertemuan_selesai_count' => fn ($q) => $q->whereNotNull('selesai_pada')])
            ->get(['id', 'modul_ajar_id', 'materi', 'updated_at']);

        $berjalan = Pertemuan::berlangsung()
            ->whereNotNull('guru_pengganti_id')
            ->with(['guruPengganti:id,name', 'modulAjarDetail:id,modul_ajar_id,materi', 'modulAjarDetail.modulAjar:id,kode_kelas'])
            ->orderBy('tanggal')
            ->get(['id', 'modul_ajar_detail_id', 'tanggal', 'guru_pengganti_id']);

        $kodeKelas = $terbuka->pluck('modulAjar.kode_kelas')
            ->merge($berjalan->pluck('modulAjarDetail.modulAjar.kode_kelas'))
            ->filter()
            ->unique();

        $kelas = $this->petaKelasRingkas($kodeKelas);

        return [
            'slot_terbuka' => $terbuka
                ->map(fn (ModulAjarDetail $d) => array_merge(
                    $kelas[$d->modulAjar?->kode_kelas] ?? $this->kelasTidakDikenal(),
                    [
                        'materi' => $d->materi,
                        'sejak' => $d->updated_at,
                        'sejak_label' => $d->updated_at?->locale('id')->diffForHumans(),
                        'ajar_ulang' => $d->pertemuan_selesai_count > 0,
                    ]
                ))
                ->sortBy('sejak')
                ->values(),
            'sedang_diajar_pengganti' => $berjalan
                ->map(fn (Pertemuan $p) => array_merge(
                    $kelas[$p->modulAjarDetail?->modulAjar?->kode_kelas] ?? $this->kelasTidakDikenal(),
                    [
                        'materi' => $p->modulAjarDetail?->materi ?? '-',
                        'pengganti' => $p->guruPengganti?->name ?? '-',
                        'tanggal' => $p->tanggal,
                        'tanggal_label' => $p->tanggal?->locale('id')->translatedFormat('d F Y'),
                    ]
                ))
                ->values(),
        ];
    }

    /**
     * @param  Collection<int, string>  $kodeKelas
     * @return array<string, array<string, mixed>>
     */
    private function petaKelasRingkas(Collection $kodeKelas): array
    {
        if ($kodeKelas->isEmpty()) {
            return [];
        }

        return Jadwal::query()
            ->whereIn('kode_kelas', $kodeKelas)
            ->with(['hari:id,name', 'sesi:id,name,start_time,end_time', 'mataPelajaran:id,name', 'guru:id,name', 'ruang:id,name'])
            ->get(['id', 'kode_kelas', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
            ->groupBy('kode_kelas')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'mapel' => $first->mataPelajaran?->name ?? '-',
                    'hari' => $first->hari?->name ?? '-',
                    'sesi' => $first->sesi?->label ?? ($first->sesi?->name ?? '-'),
                    'guru_asli' => $first->guru?->name ?? '-',
                    'ruang' => $first->ruang?->name ?? '-',
                    'jumlah_siswa' => $rows->pluck('siswa_id')->unique()->count(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function kelasTidakDikenal(): array
    {
        return [
            'mapel' => '-',
            'hari' => '-',
            'sesi' => '-',
            'guru_asli' => '-',
            'ruang' => '-',
            'jumlah_siswa' => 0,
        ];
    }

    public function pengingatJadwalWa(): array
    {
        $terakhir = JadwalTeksLog::whereDate('created_at', today())
            ->with('user:id,name')
            ->latest()
            ->first();

        return [
            'sudah_hari_ini' => (bool) $terakhir,
            'terakhir_jam' => $terakhir?->created_at?->format('H:i'),
            'terakhir_oleh' => $terakhir?->user?->name,
        ];
    }

    public function bentrokTersembunyi(): Collection
    {
        $jadwals = Jadwal::query()
            ->select(['id', 'hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'siswa_id'])
            ->get();

        $namaHari = Hari::pluck('name', 'id');
        $namaSesi = Sesi::pluck('name', 'id');
        $namaGuru = Guru::pluck('name', 'id');
        $namaRuang = Ruang::pluck('name', 'id');
        $namaSiswa = Siswa::pluck('name', 'id');

        $conflicts = collect();

        foreach ($jadwals->groupBy(fn (Jadwal $j) => "{$j->hari_id}_{$j->sesi_id}") as $slotKey => $rows) {
            [$hariId, $sesiId] = explode('_', $slotKey);
            $classKeyOf = fn (Jadwal $r) => "{$r->mata_pelajaran_id}_{$r->guru_id}_{$r->ruang_id}";

            if ($rows->unique($classKeyOf)->count() <= 1) {
                continue;
            }

            foreach ($rows->groupBy('guru_id') as $guruId => $guruRows) {
                if ($guruRows->unique($classKeyOf)->count() > 1) {
                    $conflicts->push("Guru {$namaGuru->get($guruId, 'N/A')} mengajar 2 kelas berbeda sekaligus pada {$namaHari->get($hariId, 'N/A')}, {$namaSesi->get($sesiId, 'N/A')}.");
                }
            }

            foreach ($rows->groupBy('ruang_id') as $ruangId => $ruangRows) {
                if ($ruangRows->unique($classKeyOf)->count() > 1) {
                    $conflicts->push("Ruang {$namaRuang->get($ruangId, 'N/A')} dipakai 2 kelas berbeda sekaligus pada {$namaHari->get($hariId, 'N/A')}, {$namaSesi->get($sesiId, 'N/A')}.");
                }
            }

            foreach ($rows->groupBy('siswa_id') as $siswaId => $siswaRows) {
                if ($siswaRows->unique($classKeyOf)->count() > 1) {
                    $conflicts->push("Siswa {$namaSiswa->get($siswaId, 'N/A')} terjadwal di 2 kelas berbeda sekaligus pada {$namaHari->get($hariId, 'N/A')}, {$namaSesi->get($sesiId, 'N/A')}.");
                }
            }
        }

        $conflicts = $conflicts->merge($this->bentrokLintasSesiBeririsan($jadwals, $namaHari, $namaSesi, $namaGuru, $namaRuang, $namaSiswa));

        return $conflicts->unique()->values();
    }

    private function bentrokLintasSesiBeririsan(
        Collection $jadwals,
        Collection $namaHari,
        Collection $namaSesi,
        Collection $namaGuru,
        Collection $namaRuang,
        Collection $namaSiswa
    ): Collection {
        $peta = $this->irisanSesi->peta();
        $conflicts = collect();

        $terpakai = [];
        foreach ($jadwals as $j) {
            $terpakai[$j->hari_id][$j->sesi_id]['ruang'][$j->ruang_id] = true;
            $terpakai[$j->hari_id][$j->sesi_id]['guru'][$j->guru_id] = true;
            $terpakai[$j->hari_id][$j->sesi_id]['siswa'][$j->siswa_id] = true;
        }

        $label = [
            'ruang' => ['Ruang', $namaRuang, 'dipakai'],
            'guru' => ['Guru', $namaGuru, 'mengajar'],
            'siswa' => ['Siswa', $namaSiswa, 'terjadwal'],
        ];

        foreach ($terpakai as $hariId => $perSesi) {
            foreach ($perSesi as $sesiA => $isiA) {
                foreach ($peta[$sesiA] ?? [] as $sesiB) {
                    if ($sesiB <= $sesiA || ! isset($perSesi[$sesiB])) {
                        continue;
                    }

                    foreach ($label as $jenis => [$sebutan, $nama, $kata]) {
                        foreach (array_keys($isiA[$jenis] ?? []) as $id) {
                            if (! isset($perSesi[$sesiB][$jenis][$id])) {
                                continue;
                            }

                            $conflicts->push(sprintf(
                                '%s %s %s di dua sesi yang jamnya bertindih pada %s: %s dan %s.',
                                $sebutan,
                                $nama->get($id, 'N/A'),
                                $kata,
                                $namaHari->get($hariId, 'N/A'),
                                $namaSesi->get($sesiA, 'N/A'),
                                $namaSesi->get($sesiB, 'N/A')
                            ));
                        }
                    }
                }
            }
        }

        return $conflicts;
    }
}
