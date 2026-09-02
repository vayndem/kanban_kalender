<?php

namespace App\Console\Commands;

use App\Models\Paket;
use App\Models\Siswa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Merapikan tagihan ganda (siswa + paket + periode yang sama).
 *
 * Aturan yang dipakai:
 *  - Tagihan tertua dalam satu kelompok dipertahankan sebagai induk.
 *  - Detail pembayaran di tagihan ganda diperiksa satu per satu:
 *      * kembar persis (nominal + tanggal sama dengan yang sudah ada di induk)
 *        -> pencatatan ganda akibat klik dua kali, dibuang;
 *      * "Selesai sistem" -> penutupan otomatis tanpa uang masuk, dibuang;
 *      * selain itu -> pembayaran nyata yang berbeda, DIPINDAHKAN ke induk,
 *        tidak pernah dibuang.
 *  - Setelah itu total_sudah_dibayar dan status induk dihitung ulang dari
 *    detail yang benar-benar tersisa. Bila hasilnya kurang dari harga, tagihan
 *    memang menyisakan tunggakan -- itu keadaan yang jujur, bukan kesalahan.
 *
 * Seluruh baris yang dibuang diarsipkan utuh ke koreksi_pembayaran_logs.
 * Default hanya simulasi; --force untuk benar-benar menerapkan.
 */
class RapikanDuplikatPembayaran extends Command
{
    protected $signature = 'pembayaran:rapikan-duplikat {--force : Benar-benar terapkan perubahan}';

    protected $description = 'Rapikan tagihan ganda: satukan pembayaran ke tagihan induk, arsipkan yang dibuang.';

    public function handle(): int
    {
        $terapkan = (bool) $this->option('force');

        $this->info($terapkan
            ? 'MODE TERAPKAN — perubahan akan disimpan.'
            : 'MODE SIMULASI — tidak ada satu baris pun yang diubah. Tambahkan --force untuk menerapkan.');
        $this->newLine();

        $rencana = $this->susunRencana();

        if ($rencana === []) {
            $this->info('Tidak ada tagihan ganda. Tidak ada yang perlu dirapikan.');

            return self::SUCCESS;
        }

        $this->tampilkanRencana($rencana);

        if (! $terapkan) {
            $this->warn('Simulasi selesai. Jalankan ulang dengan --force bila rencana di atas sudah benar.');

            return self::SUCCESS;
        }

        $this->terapkan($rencana);

        return self::SUCCESS;
    }

    private function susunRencana(): array
    {
        $kelompok = DB::table('pembayarans')
            ->select('id_siswa', 'id_paket', 'periode')
            ->whereNotNull('id_paket')
            ->whereNotNull('periode')
            ->groupBy('id_siswa', 'id_paket', 'periode')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $namaSiswa = Siswa::whereIn('id', $kelompok->pluck('id_siswa'))->pluck('name', 'id');
        $namaPaket = Paket::whereIn('id', $kelompok->pluck('id_paket'))->pluck('nama_paket', 'id');

        $rencana = [];

        foreach ($kelompok as $k) {
            $tagihan = DB::table('pembayarans')
                ->where('id_siswa', $k->id_siswa)
                ->where('id_paket', $k->id_paket)
                ->where('periode', $k->periode)
                ->orderBy('id')
                ->get();

            $induk = $tagihan->first();
            $ganda = $tagihan->slice(1);

            $detailInduk = DB::table('pembayaran_details')
                ->where('id_pembayaran', $induk->id)
                ->get();

            $sidikInduk = $detailInduk
                ->map(fn ($d) => $this->sidik($d))
                ->all();

            $dibuang = [];
            $dipindah = [];

            foreach ($ganda as $g) {
                foreach (DB::table('pembayaran_details')->where('id_pembayaran', $g->id)->get() as $d) {
                    if ($d->keterangan === 'Selesai sistem') {
                        $dibuang[] = ['detail' => $d, 'alasan' => 'Penutupan otomatis tanpa uang masuk'];

                        continue;
                    }

                    if (in_array($this->sidik($d), $sidikInduk, true)) {
                        $dibuang[] = ['detail' => $d, 'alasan' => 'Pencatatan ganda (nominal & tanggal sama dengan induk)'];

                        continue;
                    }

                    // Pembayaran nyata yang berbeda -> jangan dibuang.
                    $dipindah[] = $d;
                    $sidikInduk[] = $this->sidik($d);
                }
            }

            $totalBaru = (int) $detailInduk->sum('pembayaran')
                + collect($dipindah)->sum('pembayaran');

            $rencana[] = [
                'kelompok' => $k->id_siswa.'|'.$k->id_paket.'|'.$k->periode,
                'siswa' => $namaSiswa[$k->id_siswa] ?? "Siswa #{$k->id_siswa}",
                'paket' => $namaPaket[$k->id_paket] ?? "Paket #{$k->id_paket}",
                'periode' => $k->periode,
                'induk' => $induk,
                'ganda' => $ganda->values()->all(),
                'detail_dibuang' => $dibuang,
                'detail_dipindah' => $dipindah,
                'total_lama' => (int) $induk->total_sudah_dibayar,
                'total_baru' => (int) $totalBaru,
                'status_lama' => (int) $induk->status,
                'status_baru' => $this->hitungStatus((int) $totalBaru, (int) $induk->harga),
            ];
        }

        return $rencana;
    }

    private function tampilkanRencana(array $rencana): void
    {
        $tagihanDibuang = 0;
        $nilaiTagihanDibuang = 0;
        $detailDibuang = 0;
        $nilaiDetailDibuang = 0;
        $dipindah = 0;
        $berubahStatus = [];

        foreach ($rencana as $r) {
            $tagihanDibuang += count($r['ganda']);
            $nilaiTagihanDibuang += collect($r['ganda'])->sum('harga');
            $detailDibuang += count($r['detail_dibuang']);
            $nilaiDetailDibuang += collect($r['detail_dibuang'])->sum(fn ($x) => (int) $x['detail']->pembayaran);
            $dipindah += count($r['detail_dipindah']);

            if ($r['status_baru'] !== $r['status_lama'] || $r['total_baru'] !== $r['total_lama']) {
                $berubahStatus[] = $r;
            }
        }

        $this->line('<fg=cyan>RINGKASAN</>');
        $this->line('  Kelompok tagihan ganda            : '.count($rencana));
        $this->line('  Baris tagihan dibuang (diarsipkan): '.$tagihanDibuang.'  (Rp '.number_format($nilaiTagihanDibuang, 0, ',', '.').')');
        $this->line('  Catatan pembayaran dibuang        : '.$detailDibuang.'  (Rp '.number_format($nilaiDetailDibuang, 0, ',', '.').')');
        $this->line('  Pembayaran nyata dipindah ke induk: '.$dipindah);
        $this->newLine();

        if ($berubahStatus !== []) {
            $this->warn('Tagihan induk yang status/nilainya berubah setelah dirapikan:');
            foreach ($berubahStatus as $r) {
                $label = [0 => 'Belum Bayar', 1 => 'Tertagih', 2 => 'Lunas'];
                $this->line(sprintf(
                    '    %-32s Rp %-10s | dibayar %s -> %s | %s -> %s',
                    mb_substr($r['siswa'], 0, 32),
                    number_format((int) $r['induk']->harga, 0, ',', '.'),
                    number_format($r['total_lama'], 0, ',', '.'),
                    number_format($r['total_baru'], 0, ',', '.'),
                    $label[$r['status_lama']] ?? $r['status_lama'],
                    $label[$r['status_baru']] ?? $r['status_baru']
                ));
            }
            $this->newLine();
        }

        $this->line('<fg=cyan>CONTOH RINCIAN (5 kelompok pertama)</>');
        foreach (array_slice($rencana, 0, 5) as $r) {
            $this->line("  <fg=yellow>{$r['siswa']}</> — {$r['paket']} — {$r['periode']}");
            $this->line("     DIPERTAHANKAN #{$r['induk']->id} (dibuat {$r['induk']->created_at})");
            foreach ($r['ganda'] as $g) {
                $this->line("     DIBUANG       #{$g->id} (dibuat {$g->created_at}) — {$g->keterangan}");
            }
            foreach ($r['detail_dibuang'] as $d) {
                $this->line('        - catatan #'.$d['detail']->id.' Rp '.number_format((int) $d['detail']->pembayaran, 0, ',', '.').' dibuang: '.$d['alasan']);
            }
            foreach ($r['detail_dipindah'] as $d) {
                $this->line('        - catatan #'.$d->id.' Rp '.number_format((int) $d->pembayaran, 0, ',', '.').' DIPINDAH ke induk');
            }
        }
        $this->newLine();
    }

    private function terapkan(array $rencana): void
    {
        DB::transaction(function () use ($rencana) {
            foreach ($rencana as $r) {
                foreach ($r['detail_dipindah'] as $d) {
                    DB::table('pembayaran_details')
                        ->where('id', $d->id)
                        ->update(['id_pembayaran' => $r['induk']->id]);
                }

                foreach ($r['ganda'] as $g) {
                    $detailTersisa = DB::table('pembayaran_details')->where('id_pembayaran', $g->id)->get();

                    DB::table('koreksi_pembayaran_logs')->insert([
                        'kelompok' => $r['kelompok'],
                        'id_pembayaran_induk' => $r['induk']->id,
                        'id_pembayaran_dibuang' => $g->id,
                        'nilai_tagihan_dibuang' => (int) $g->harga,
                        'nilai_detail_dibuang' => (int) $detailTersisa->sum('pembayaran'),
                        'alasan' => 'Tagihan ganda untuk siswa, paket, dan periode yang sama',
                        'data_asli' => json_encode([
                            'pembayaran' => $g,
                            'pembayaran_details' => $detailTersisa,
                        ], JSON_UNESCAPED_UNICODE),
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Detail sisa ikut terhapus lewat cascade; isinya sudah
                    // diarsipkan utuh pada baris log di atas.
                    DB::table('pembayarans')->where('id', $g->id)->delete();
                }

                DB::table('pembayarans')->where('id', $r['induk']->id)->update([
                    'total_sudah_dibayar' => $r['total_baru'],
                    'status' => $r['status_baru'],
                    'updated_at' => now(),
                ]);
            }
        });

        $this->info('Selesai. '.count($rencana).' kelompok dirapikan dalam satu transaksi.');
        $this->line('Arsip lengkap baris yang dibuang tersimpan di tabel koreksi_pembayaran_logs.');
        $this->newLine();
        $this->line('Verifikasi: php artisan pembayaran:audit-duplikat');
    }

    private function sidik(object $detail): string
    {
        return $detail->pembayaran.'@'.substr((string) $detail->created_at, 0, 10);
    }

    private function hitungStatus(int $dibayar, int $harga): int
    {
        return match (true) {
            $dibayar >= $harga && $harga > 0 => 2,
            $dibayar > 0 => 1,
            default => 0,
        };
    }
}
