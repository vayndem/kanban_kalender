<?php

namespace App\Console\Commands;

use App\Models\Paket;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mendeteksi tagihan ganda (satu siswa tertagih paket yang sama dua kali dalam
 * satu bulan) -- pola yang muncul ketika tagihan manual dan penagihan massal
 * dibuat untuk paket dan periode yang sama.
 *
 * Command ini SENGAJA hanya membaca dan melaporkan. Tidak ada penghapusan
 * otomatis: ini menyangkut uang dan bukti pembukuan, jadi keputusan koreksi
 * harus di tangan manusia.
 */
class AuditPembayaranDuplikat extends Command
{
    protected $signature = 'pembayaran:audit-duplikat';

    protected $description = 'Cari tagihan ganda (siswa + paket + bulan yang sama). Hanya membaca, tidak mengubah data.';

    public function handle(): int
    {
        $this->info('Memeriksa tagihan ganda...');
        $this->newLine();

        $groups = Schema::hasColumn('pembayarans', 'id_paket')
            ? $this->detectByAnchor()
            : $this->detectByKeterangan();

        if ($groups->isEmpty()) {
            $this->info('Bersih. Tidak ditemukan tagihan ganda.');

            return self::SUCCESS;
        }

        $this->warn('Ditemukan '.$groups->count().' kelompok tagihan ganda:');
        $this->newLine();

        $totalNilaiGanda = 0;

        foreach ($groups as $group) {
            $this->line("<fg=yellow>{$group['siswa']}</> — paket <fg=cyan>{$group['paket']}</> — periode <fg=cyan>{$group['periode']}</>");

            foreach ($group['rows'] as $row) {
                $status = match ((int) $row->status) {
                    1 => 'Tertagih',
                    2 => 'Lunas',
                    default => 'Belum Bayar',
                };

                $this->line(sprintf(
                    '    #%-5s Rp %-12s %-12s dibuat %s',
                    $row->id,
                    number_format((int) $row->harga, 0, ',', '.'),
                    $status,
                    Carbon::parse($row->created_at)->format('d M Y H:i')
                ));
                $this->line("          keterangan: {$row->keterangan}");
            }

            $kelebihan = collect($group['rows'])->slice(1)->sum(fn ($row) => (int) $row->harga);
            $totalNilaiGanda += $kelebihan;
            $this->line('    <fg=red>Kelebihan tagihan: Rp '.number_format($kelebihan, 0, ',', '.').'</>');
            $this->newLine();
        }

        $this->error('Total nilai tagihan berlebih: Rp '.number_format($totalNilaiGanda, 0, ',', '.'));
        $this->newLine();
        $this->line('Langkah berikutnya (dilakukan manual, tidak otomatis):');
        $this->line('  1. Periksa tiap kelompok di atas, tentukan baris mana yang sah.');
        $this->line('  2. Rapikan baris yang berlebih lewat halaman admin.');
        $this->line('  3. Jalankan ulang command ini sampai bersih.');
        $this->line('  4. Baru jalankan "php artisan migrate" untuk memasang kunci UNIQUE anti-ganda.');

        return self::FAILURE;
    }

    /**
     * Deteksi setelah kolom anchor tersedia: langsung dari (id_siswa, id_paket, periode).
     */
    private function detectByAnchor()
    {
        $duplicates = DB::table('pembayarans')
            ->select('id_siswa', 'id_paket', 'periode', DB::raw('COUNT(*) as jml'))
            ->whereNotNull('id_paket')
            ->whereNotNull('periode')
            ->groupBy('id_siswa', 'id_paket', 'periode')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $students = Siswa::whereIn('id', $duplicates->pluck('id_siswa'))->pluck('name', 'id');
        $packages = Paket::whereIn('id', $duplicates->pluck('id_paket'))->pluck('nama_paket', 'id');

        return $duplicates->map(function ($group) use ($students, $packages) {
            $rows = DB::table('pembayarans')
                ->where('id_siswa', $group->id_siswa)
                ->where('id_paket', $group->id_paket)
                ->where('periode', $group->periode)
                ->orderBy('id')
                ->get(['id', 'harga', 'status', 'keterangan', 'created_at']);

            return [
                'siswa' => $students[$group->id_siswa] ?? "Siswa #{$group->id_siswa}",
                'paket' => $packages[$group->id_paket] ?? "Paket #{$group->id_paket}",
                'periode' => $group->periode,
                'rows' => $rows->all(),
            ];
        })->values();
    }

    /**
     * Deteksi sebelum migrasi anchor dijalankan: cocokkan nama paket di dalam
     * teks keterangan, lalu kelompokkan per siswa + bulan pembuatan.
     */
    private function detectByKeterangan()
    {
        $packages = Paket::query()->get(['id', 'nama_paket'])
            ->sortByDesc(fn ($package) => mb_strlen((string) $package->nama_paket))
            ->values();

        if ($packages->isEmpty()) {
            return collect();
        }

        $students = Siswa::pluck('name', 'id');
        $buckets = [];

        DB::table('pembayarans')
            ->orderBy('id')
            ->chunkById(500, function ($payments) use ($packages, &$buckets) {
                foreach ($payments as $payment) {
                    $keterangan = (string) $payment->keterangan;
                    if ($keterangan === '') {
                        continue;
                    }

                    $matched = $packages->first(
                        fn ($package) => $package->nama_paket !== null
                            && $package->nama_paket !== ''
                            && str_contains($keterangan, (string) $package->nama_paket)
                    );

                    if (! $matched) {
                        continue;
                    }

                    $periode = Carbon::parse($payment->created_at)->format('Y-m');
                    $buckets[$payment->id_siswa.'|'.$matched->id.'|'.$periode][] = $payment;
                }
            });

        return collect($buckets)
            ->filter(fn ($rows) => count($rows) > 1)
            ->map(function ($rows, $key) use ($students, $packages) {
                [$siswaId, $paketId, $periode] = explode('|', $key);

                return [
                    'siswa' => $students[$siswaId] ?? "Siswa #{$siswaId}",
                    'paket' => $packages->firstWhere('id', (int) $paketId)?->nama_paket ?? "Paket #{$paketId}",
                    'periode' => $periode,
                    'rows' => $rows,
                ];
            })
            ->values();
    }
}
