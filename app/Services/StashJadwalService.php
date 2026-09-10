<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\StashPemulihanLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StashJadwalService
{
    public const PENANDA_APLIKASI = 'E-Ling-Course';

    private const KUNCI_WAJIB = ['h', 's', 'm', 'g', 'r', 'si'];

    public function __construct(private readonly IrisanSesiService $irisanSesi) {}

    public function bungkus(?Collection $jadwals = null): array
    {
        $baris = ($jadwals ?? Jadwal::all())->map(fn (Jadwal $j) => [
            'h' => $j->hari_id,
            's' => $j->sesi_id,
            'm' => $j->mata_pelajaran_id,
            'g' => $j->guru_id,
            'r' => $j->ruang_id,
            'si' => $j->siswa_id,
            'k' => $j->kode_kelas,
        ])->values();

        return [
            'app' => self::PENANDA_APLIKASI,
            'version' => '1.0',
            'timestamp' => now()->toDateTimeString(),
            'content' => $baris,
        ];
    }

    public function encode(array $data): string
    {
        return base64_encode(json_encode($data));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function baca(string $isiBerkas): array
    {
        $data = json_decode(base64_decode($isiBerkas, true) ?: '', true);

        if (! is_array($data) || ($data['app'] ?? null) !== self::PENANDA_APLIKASI) {
            throw new RuntimeException('Format file stash tidak dikenali.');
        }

        if (! isset($data['content']) || ! is_array($data['content'])) {
            throw new RuntimeException('Isi file stash kosong atau rusak.');
        }

        foreach ($data['content'] as $i => $baris) {
            if (! is_array($baris)) {
                throw new RuntimeException('Baris ke-'.($i + 1).' pada file stash tidak berbentuk data jadwal.');
            }

            foreach (self::KUNCI_WAJIB as $kunci) {
                if (! isset($baris[$kunci]) || ! is_numeric($baris[$kunci])) {
                    throw new RuntimeException('Baris ke-'.($i + 1).' pada file stash tidak lengkap. Tidak ada data yang diubah.');
                }
            }
        }

        return $data['content'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     */
    public function pastikanReferensiAda(array $baris): void
    {
        $rujukan = [
            'h' => ['label' => 'Hari', 'ada' => Hari::pluck('id')->all()],
            's' => ['label' => 'Sesi', 'ada' => Sesi::pluck('id')->all()],
            'm' => ['label' => 'Mata pelajaran', 'ada' => MataPelajaran::pluck('id')->all()],
            'g' => ['label' => 'Guru', 'ada' => Guru::pluck('id')->all()],
            'r' => ['label' => 'Ruang', 'ada' => Ruang::pluck('id')->all()],
            'si' => ['label' => 'Siswa', 'ada' => Siswa::pluck('id')->all()],
        ];

        $hilang = [];
        foreach ($rujukan as $kunci => $info) {
            $adaSet = array_flip(array_map('intval', $info['ada']));
            $tidakAda = [];

            foreach ($baris as $b) {
                $id = (int) $b[$kunci];
                if (! isset($adaSet[$id])) {
                    $tidakAda[$id] = true;
                }
            }

            if ($tidakAda !== []) {
                $hilang[] = $info['label'].' #'.implode(', #', array_slice(array_keys($tidakAda), 0, 5));
            }
        }

        if ($hilang !== []) {
            throw new RuntimeException(
                'File stash menunjuk data yang sudah tidak ada: '.implode('; ', $hilang).
                '. Tidak ada data yang diubah.'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     */
    public function hitungBentrok(array $baris): int
    {
        $peta = $this->irisanSesi->peta();

        $terpakai = [];
        foreach ($baris as $b) {
            $terpakai[(int) $b['h']][(int) $b['s']]['ruang'][(int) $b['r']] = true;
            $terpakai[(int) $b['h']][(int) $b['s']]['guru'][(int) $b['g']] = true;
            $terpakai[(int) $b['h']][(int) $b['s']]['siswa'][(int) $b['si']] = true;
        }

        $jumlah = 0;
        foreach ($terpakai as $perSesi) {
            foreach ($perSesi as $sesiA => $isiA) {
                foreach ($peta[$sesiA] ?? [] as $sesiB) {
                    if ($sesiB <= $sesiA || ! isset($perSesi[$sesiB])) {
                        continue;
                    }

                    foreach (['ruang', 'guru', 'siswa'] as $jenis) {
                        foreach (array_keys($isiA[$jenis] ?? []) as $id) {
                            if (isset($perSesi[$sesiB][$jenis][$id])) {
                                $jumlah++;
                            }
                        }
                    }
                }
            }
        }

        return $jumlah;
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     */
    public function pulihkan(array $baris, ?User $aktor, callable $backfillKodeKelas): StashPemulihanLog
    {
        $this->pastikanReferensiAda($baris);
        $bentrok = $this->hitungBentrok($baris);

        return DB::transaction(function () use ($baris, $aktor, $backfillKodeKelas, $bentrok) {
            $sebelum = $this->bungkus();
            $jumlahSebelum = count($sebelum['content']);

            Jadwal::query()->delete();

            $now = now();
            $insert = array_map(fn ($b) => [
                'hari_id' => (int) $b['h'],
                'sesi_id' => (int) $b['s'],
                'mata_pelajaran_id' => (int) $b['m'],
                'guru_id' => (int) $b['g'],
                'ruang_id' => (int) $b['r'],
                'siswa_id' => (int) $b['si'],
                'kode_kelas' => blank($b['k'] ?? null) ? null : $b['k'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $baris);

            foreach (array_chunk($insert, 500) as $bagian) {
                Jadwal::insert($bagian);
            }

            $backfillKodeKelas();

            return StashPemulihanLog::create([
                'dipulihkan_oleh' => $aktor?->id,
                'jumlah_sebelum' => $jumlahSebelum,
                'jumlah_sesudah' => count($insert),
                'bentrok_masuk' => $bentrok,
                'isi_sebelum' => $this->encode($sebelum),
            ]);
        });
    }
}
