<?php

namespace App\Console\Commands;

use App\Support\NomorHp;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalisasiNomorHp extends Command
{
    protected $signature = 'pembayaran:normalisasi-hp {--force : Benar-benar tulis perubahan ke database}';

    protected $description = 'Seragamkan format no_hp ke +62 di seluruh tabel terkait. Default: simulasi saja.';

    private const TABEL = ['siswas', 'pembayarans', 'arsips', 'diskons'];

    public function handle(): int
    {
        $tulis = (bool) $this->option('force');

        $this->info($tulis
            ? 'MODE TULIS — perubahan akan disimpan.'
            : 'MODE SIMULASI — tidak ada satu baris pun yang diubah. Tambahkan --force untuk menulis.');
        $this->newLine();

        $rencana = $this->susunRencana();

        if ($rencana->isEmpty()) {
            $this->info('Semua nomor sudah berformat +62. Tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        foreach ($rencana->groupBy('tabel') as $tabel => $baris) {
            $this->line("<fg=cyan>{$tabel}</> — " . $baris->count() . ' baris akan diubah');
            foreach ($baris->take(5) as $b) {
                $this->line(sprintf('    #%-8s %-22s -> %s', $b['id'], '[' . $b['lama'] . ']', $b['baru']));
            }
            if ($baris->count() > 5) {
                $this->line('    ... dan ' . ($baris->count() - 5) . ' baris lainnya');
            }
            $this->newLine();
        }

        $this->ringkasanPenggabungan();

        if (! $tulis) {
            $this->warn('Simulasi selesai. Jalankan ulang dengan --force bila hasil di atas sudah benar.');

            return self::SUCCESS;
        }

        $this->terapkan($rencana);

        return self::SUCCESS;
    }

    public static function normalkan(?string $nilai): ?string
    {
        return NomorHp::normalkan($nilai);
    }

    private function susunRencana(): Collection
    {
        $rencana = collect();

        foreach (self::TABEL as $tabel) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, 'no_hp')) {
                continue;
            }

            DB::table($tabel)
                ->whereNotNull('no_hp')
                ->orderBy('id')
                ->chunkById(500, function ($baris) use ($tabel, $rencana) {
                    foreach ($baris as $row) {
                        $baru = self::normalkan($row->no_hp);

                        if ($baru === null || $baru === $row->no_hp) {
                            continue;
                        }

                        $rencana->push([
                            'tabel' => $tabel,
                            'id' => $row->id,
                            'lama' => $row->no_hp,
                            'baru' => $baru,
                        ]);
                    }
                });
        }

        return $rencana;
    }

    private function ringkasanPenggabungan(): void
    {
        $peta = [];
        foreach (DB::table('siswas')->whereNotNull('no_hp')->get(['name', 'no_hp']) as $siswa) {
            $baru = self::normalkan($siswa->no_hp);
            if ($baru === null) {
                continue;
            }
            $peta[$baru][$siswa->no_hp][] = $siswa->name;
        }

        $gabung = collect($peta)->filter(fn($bentuk) => count($bentuk) > 1);

        if ($gabung->isEmpty()) {
            return;
        }

        $this->warn('Perhatian: ' . $gabung->count() . ' grup keluarga akan MENYATU karena sebelumnya terpecah oleh beda format:');
        foreach ($gabung as $normal => $bentuk) {
            $nama = collect($bentuk)->flatten()->map(fn($n) => mb_substr($n, 0, 26))->implode(' + ');
            $this->line("    {$normal} : {$nama}");
        }
        $this->newLine();
    }

    private function terapkan(Collection $rencana): void
    {
        DB::transaction(function () use ($rencana) {
            foreach ($rencana as $b) {
                DB::table($b['tabel'])->where('id', $b['id'])->update(['no_hp' => $b['baru']]);
            }
        });

        $this->info('Selesai. ' . $rencana->count() . ' baris diperbarui dalam satu transaksi.');
        foreach ($rencana->groupBy('tabel') as $tabel => $baris) {
            $this->line("    {$tabel}: " . $baris->count() . ' baris');
        }

        $this->newLine();
        $this->line('Verifikasi ulang dengan menjalankan command ini lagi tanpa --force.');
    }
}
