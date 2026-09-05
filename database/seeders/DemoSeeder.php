<?php

namespace Database\Seeders;

use App\Models\Arsip;
use App\Models\Diskon;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\Tanda;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data demo yang menggambarkan bimbel sedang berjalan.
 *
 * Jalankan: php artisan db:seed --class=DemoSeeder
 *
 * Isinya sengaja dibuat menyerupai keadaan nyata, bukan sekadar baris acak:
 *  - keluarga dengan kakak-adik yang berbagi satu nomor HP;
 *  - jadwal kelas berisi beberapa siswa sekaligus, tanpa bentrok guru/ruang/siswa;
 *  - riwayat tagihan tiga bulan dengan status bercampur (lunas, dicicil, belum bayar);
 *  - cicilan bertahap yang jumlahnya cocok dengan total_sudah_dibayar;
 *  - diskon keluarga dan diskon universal;
 *  - siswa yang sudah diarsipkan dan catatan pada beberapa siswa.
 *
 * Semua nomor HP memakai format +62 dan setiap tagihan paket memakai anchor
 * (id_paket + periode), sehingga data ini patuh pada kunci anti-tagihan-ganda.
 * Seeder bersifat menambah dan aman dijalankan ulang.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Menyiapkan data demo E-Ling Course...');

        $master = $this->masterData();
        $siswa = $this->siswaDanKeluarga($master['paket']);
        $this->jadwalKelas($master, $siswa);
        $this->diskonKeluarga($siswa);
        $this->riwayatPembayaran($siswa, $master['paket']);
        $this->catatanSiswa($siswa);
        $this->siswaArsip($master['paket']);

        $this->ringkasan();
    }

    private function masterData(): array
    {
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $nama) {
            Hari::firstOrCreate(['name' => $nama]);
        }

        $sesiData = [
            ['Sesi 1', '13:30', '15:00'],
            ['Sesi 2', '15:30', '16:30'],
            ['Sesi 3', '16:30', '17:30'],
            ['Sesi 4', '18:30', '20:00'],
        ];
        foreach ($sesiData as [$nama, $mulai, $selesai]) {
            Sesi::firstOrCreate(['name' => $nama], ['start_time' => $mulai, 'end_time' => $selesai]);
        }

        $guru = collect(['Bu Rina', 'Bu Sekar', 'Pak Anwar', 'Bu Melati', 'Pak Bagas', 'Bu Kirana'])
            ->mapWithKeys(fn ($nama) => [$nama => Guru::firstOrCreate(['name' => $nama])]);

        $ruang = collect(['Ruang Anggrek', 'Ruang Melati', 'Ruang Dahlia', 'Ruang Kenanga'])
            ->mapWithKeys(fn ($nama) => [$nama => Ruang::firstOrCreate(['name' => $nama])]);

        $mapel = collect(['English Class', 'Matematika', 'Calistung', 'IPA Terpadu', 'Tematik SD', 'Persiapan TKA'])
            ->mapWithKeys(fn ($nama) => [$nama => MataPelajaran::firstOrCreate(['name' => $nama])]);

        $paketData = [
            ['TK Calistung 2X/Minggu', 250000, 2],
            ['SD English Class 2X/Minggu', 300000, 2],
            ['SD All Mapel 3X/Minggu', 350000, 3],
            ['SMP Matematika 2X/Minggu', 325000, 2],
            ['Persiapan TKA 3X/Minggu', 400000, 3],
            ['Privat Intensif 1X/Minggu', 175000, 1],
        ];
        $paket = collect($paketData)->mapWithKeys(fn ($p) => [
            $p[0] => Paket::firstOrCreate(['nama_paket' => $p[0]], ['harga' => $p[1], 'pertemuan' => $p[2]]),
        ]);

        return compact('guru', 'ruang', 'mapel', 'paket');
    }

    /**
     * Siswa dikelompokkan per keluarga: satu nomor HP bisa dipakai beberapa
     * anak, persis seperti data sebenarnya. Format nomor selalu +62.
     */
    private function siswaDanKeluarga($paket): array
    {
        $keluarga = [
            // [no_hp, [ [nama, panggilan, kelas, paket utama, paket kedua] ... ] ]
            ['+6281234500001', [
                ['Nayla Kirana Putri', 'Nayla', '4', 'SD All Mapel 3X/Minggu', null],
                ['Rafa Ardhito Putra', 'Rafa', '2', 'SD English Class 2X/Minggu', null],
            ]],
            ['+6281234500002', [
                ['Bimasena Arya Witjaksono', 'Bima', '8', 'SMP Matematika 2X/Minggu', 'Privat Intensif 1X/Minggu'],
                ['Satria Dharma Witjaksono', 'Satria', '5', 'SD All Mapel 3X/Minggu', null],
            ]],
            ['+6281234500003', [
                ['Zahra Aulia Rahmadani', 'Zahra', '6', 'Persiapan TKA 3X/Minggu', null],
            ]],
            ['+6281234500004', [
                ['Kenzie Alvaro Pratama', 'Kenzie', 'TK B', 'TK Calistung 2X/Minggu', null],
                ['Kayla Anindya Pratama', 'Kayla', '1', 'SD English Class 2X/Minggu', null],
            ]],
            ['+6281234500005', [
                ['Farrel Adityo Nugroho', 'Farrel', '9', 'SMP Matematika 2X/Minggu', null],
            ]],
            ['+6281234500006', [
                ['Alesha Kaila Ramadhani', 'Alesha', '3', 'SD English Class 2X/Minggu', null],
            ]],
            ['+6281234500007', [
                ['Dimas Bagaskara Wibowo', 'Dimas', '7', 'SMP Matematika 2X/Minggu', null],
                ['Danish Ravindra Wibowo', 'Danish', '4', 'SD All Mapel 3X/Minggu', null],
            ]],
            ['+6281234500008', [
                ['Queensha Azalia Santoso', 'Queen', 'TK B', 'TK Calistung 2X/Minggu', null],
            ]],
            ['+6281234500009', [
                ['Arkana Yusuf Maulana', 'Arka', '6', 'Persiapan TKA 3X/Minggu', null],
            ]],
            ['+6281234500010', [
                ['Naura Shafiyya Hakim', 'Naura', '5', 'SD All Mapel 3X/Minggu', null],
                ['Nadhif Fauzan Hakim', 'Nadhif', '2', 'SD English Class 2X/Minggu', null],
            ]],
        ];

        $hasil = [];
        foreach ($keluarga as [$noHp, $anakAnak]) {
            foreach ($anakAnak as [$nama, $panggilan, $kelas, $paketUtama, $paketKedua]) {
                $hasil[$nama] = Siswa::firstOrCreate(
                    ['name' => $nama],
                    [
                        'panggilan' => $panggilan,
                        'kelas' => $kelas,
                        'no_hp' => $noHp,
                        'paket_pembayaran' => $paket[$paketUtama]->id,
                        'paket_pembayaran_2' => $paketKedua ? $paket[$paketKedua]->id : null,
                    ]
                );
            }
        }

        return $hasil;
    }

    /**
     * Satu kelas = satu kombinasi hari/sesi/mapel/guru/ruang yang diisi
     * beberapa siswa. Susunan di bawah sengaja dirancang agar tidak ada guru,
     * ruang, maupun siswa yang terpakai dua kali pada hari & sesi yang sama --
     * aturan yang sama dengan validasi bentrok di JadwalController.
     */
    private function jadwalKelas(array $master, array $siswa): void
    {
        $kelas = [
            ['Senin', 'Sesi 1', 'English Class', 'Bu Rina', 'Ruang Anggrek', ['Rafa Ardhito Putra', 'Kayla Anindya Pratama', 'Nadhif Fauzan Hakim']],
            ['Senin', 'Sesi 1', 'Matematika', 'Pak Anwar', 'Ruang Melati', ['Bimasena Arya Witjaksono', 'Farrel Adityo Nugroho']],
            ['Senin', 'Sesi 2', 'Calistung', 'Bu Melati', 'Ruang Anggrek', ['Kenzie Alvaro Pratama', 'Queensha Azalia Santoso']],
            ['Senin', 'Sesi 2', 'Tematik SD', 'Bu Sekar', 'Ruang Dahlia', ['Nayla Kirana Putri', 'Danish Ravindra Wibowo']],

            ['Selasa', 'Sesi 1', 'Persiapan TKA', 'Bu Kirana', 'Ruang Kenanga', ['Zahra Aulia Rahmadani', 'Arkana Yusuf Maulana']],
            ['Selasa', 'Sesi 2', 'English Class', 'Bu Rina', 'Ruang Anggrek', ['Alesha Kaila Ramadhani', 'Rafa Ardhito Putra']],
            ['Selasa', 'Sesi 3', 'Matematika', 'Pak Anwar', 'Ruang Melati', ['Dimas Bagaskara Wibowo', 'Farrel Adityo Nugroho']],

            ['Rabu', 'Sesi 1', 'Tematik SD', 'Bu Sekar', 'Ruang Dahlia', ['Naura Shafiyya Hakim', 'Satria Dharma Witjaksono']],
            ['Rabu', 'Sesi 2', 'IPA Terpadu', 'Pak Bagas', 'Ruang Melati', ['Nayla Kirana Putri', 'Danish Ravindra Wibowo']],
            ['Rabu', 'Sesi 3', 'Persiapan TKA', 'Bu Kirana', 'Ruang Kenanga', ['Zahra Aulia Rahmadani']],

            ['Kamis', 'Sesi 1', 'English Class', 'Bu Rina', 'Ruang Anggrek', ['Kayla Anindya Pratama', 'Nadhif Fauzan Hakim', 'Alesha Kaila Ramadhani']],
            ['Kamis', 'Sesi 2', 'Calistung', 'Bu Melati', 'Ruang Dahlia', ['Kenzie Alvaro Pratama', 'Queensha Azalia Santoso']],
            ['Kamis', 'Sesi 3', 'Matematika', 'Pak Anwar', 'Ruang Melati', ['Bimasena Arya Witjaksono', 'Dimas Bagaskara Wibowo']],

            ['Jumat', 'Sesi 2', 'Tematik SD', 'Bu Sekar', 'Ruang Anggrek', ['Naura Shafiyya Hakim', 'Satria Dharma Witjaksono']],
            ['Jumat', 'Sesi 3', 'Persiapan TKA', 'Bu Kirana', 'Ruang Kenanga', ['Arkana Yusuf Maulana']],

            ['Sabtu', 'Sesi 1', 'IPA Terpadu', 'Pak Bagas', 'Ruang Melati', ['Nayla Kirana Putri', 'Naura Shafiyya Hakim']],
            ['Sabtu', 'Sesi 2', 'Matematika', 'Pak Anwar', 'Ruang Dahlia', ['Farrel Adityo Nugroho']],
        ];

        $terpakai = ['guru' => [], 'ruang' => [], 'siswa' => []];

        foreach ($kelas as [$hari, $sesi, $mapel, $guru, $ruang, $daftarSiswa]) {
            $hariId = Hari::where('name', $hari)->value('id');
            $sesiId = Sesi::where('name', $sesi)->value('id');
            $slot = $hariId.'-'.$sesiId;

            $kunciGuru = $slot.'-'.$master['guru'][$guru]->id;
            $kunciRuang = $slot.'-'.$master['ruang'][$ruang]->id;

            if (isset($terpakai['guru'][$kunciGuru]) || isset($terpakai['ruang'][$kunciRuang])) {
                $this->command?->warn("Lewati kelas bentrok: {$hari} {$sesi} {$mapel}");

                continue;
            }

            $terpakai['guru'][$kunciGuru] = true;
            $terpakai['ruang'][$kunciRuang] = true;

            $kodeKelas = (string) Str::uuid();

            foreach ($daftarSiswa as $nama) {
                if (! isset($siswa[$nama])) {
                    continue;
                }

                $kunciSiswa = $slot.'-'.$siswa[$nama]->id;
                if (isset($terpakai['siswa'][$kunciSiswa])) {
                    continue;
                }
                $terpakai['siswa'][$kunciSiswa] = true;

                Jadwal::firstOrCreate([
                    'siswa_id' => $siswa[$nama]->id,
                    'hari_id' => $hariId,
                    'sesi_id' => $sesiId,
                    'mata_pelajaran_id' => $master['mapel'][$mapel]->id,
                    'guru_id' => $master['guru'][$guru]->id,
                    'ruang_id' => $master['ruang'][$ruang]->id,
                ], [
                    'kode_kelas' => $kodeKelas,
                ]);
            }
        }
    }

    private function diskonKeluarga(array $siswa): void
    {
        // Diskon kakak-adik untuk dua keluarga.
        Diskon::firstOrCreate(
            ['no_hp' => '+6281234500002'],
            ['diskon' => 50000, 'keterangan' => 'Diskon kakak beradik']
        );
        Diskon::firstOrCreate(
            ['no_hp' => '+6281234500007'],
            ['diskon' => 50000, 'keterangan' => 'Diskon kakak beradik']
        );

        // Satu diskon universal yang berlaku ke semua keluarga.
        Diskon::firstOrCreate(
            ['no_hp' => null],
            ['diskon' => 25000, 'keterangan' => 'Promo awal semester']
        );
    }

    /**
     * Tagihan tiga bulan terakhir.
     *
     * Dua bulan lalu sudah beres semua, bulan lalu mulai bercampur, dan bulan
     * berjalan sengaja menyisakan tagihan belum dibayar serta cicilan yang baru
     * separuh -- supaya progres bayar, tombol Catat Bayar, dan kunci penagihan
     * massal semuanya bisa dicoba.
     */
    private function riwayatPembayaran(array $siswa, $paket): void
    {
        $rencana = [
            // bulan ke belakang => [nama siswa => [status, porsi dibayar]]
            2 => 'lunas_semua',
            1 => 'campur',
            0 => 'bulan_berjalan',
        ];

        foreach ($rencana as $mundur => $mode) {
            $saat = Carbon::now()->subMonths($mundur);
            $periode = $saat->format('Y-m');
            $label = $saat->translatedFormat('F Y');

            $urutan = 0;
            foreach ($siswa as $nama => $s) {
                $urutan++;
                foreach (['paket_pembayaran', 'paket_pembayaran_2'] as $kolom) {
                    $paketId = $s->{$kolom};
                    if (! $paketId) {
                        continue;
                    }

                    $p = $paket->firstWhere('id', $paketId);
                    if (! $p) {
                        continue;
                    }

                    [$status, $dibayar] = $this->tentukanStatus($mode, $urutan, (int) $p->harga);

                    $tagihan = Pembayaran::firstOrCreate(
                        [
                            'id_siswa' => $s->id,
                            'id_paket' => $p->id,
                            'periode' => $periode,
                        ],
                        [
                            'no_hp' => $s->no_hp,
                            'harga' => $p->harga,
                            'keterangan' => "Tagihan Paket {$p->nama_paket} - {$label}",
                            'status' => $status,
                            'total_sudah_dibayar' => $dibayar,
                            'pembayaran_via' => $urutan % 3 === 0 ? 1 : 0,
                            'tanggal_pembayaran' => $dibayar > 0 ? $saat->copy()->day(min(12, $saat->daysInMonth))->toDateString() : null,
                        ]
                    );

                    // created_at/updated_at bukan bagian $fillable Pembayaran
                    // (dijaga begitu di seluruh app supaya controller tidak
                    // bisa memalsukan tanggal lewat mass-assignment), jadi
                    // dibekukan lewat forceFill di sini, hanya untuk baris
                    // yang baru dibuat -- reseed tidak boleh mengubah tanggal
                    // baris yang sudah ada.
                    if ($tagihan->wasRecentlyCreated) {
                        $waktu = $saat->copy()->startOfMonth()->addDays(2)->setTime(9, 15);
                        $tagihan->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->save();
                    }

                    $this->catatanSetoran($tagihan, $saat, (int) $dibayar, (int) $p->harga);
                }
            }
        }
    }

    /**
     * @return array{0:int,1:int} [status, total sudah dibayar]
     */
    private function tentukanStatus(string $mode, int $urutan, int $harga): array
    {
        if ($mode === 'lunas_semua') {
            return [2, $harga];
        }

        if ($mode === 'campur') {
            return match ($urutan % 4) {
                0 => [1, (int) round($harga * 0.5)],   // baru separuh
                default => [2, $harga],                 // sudah lunas
            };
        }

        // Bulan berjalan: sebagian belum bayar, sebagian dicicil, sebagian lunas.
        return match ($urutan % 3) {
            0 => [0, 0],                                // belum bayar
            1 => [1, (int) round($harga * 0.4)],        // dicicil sebagian
            default => [2, $harga],                     // lunas
        };
    }

    /**
     * Detail setoran dibuat agar jumlahnya persis sama dengan
     * total_sudah_dibayar pada tagihan -- buku besar dan header harus cocok.
     */
    private function catatanSetoran(Pembayaran $tagihan, Carbon $saat, int $dibayar, int $harga): void
    {
        if ($dibayar <= 0 || $tagihan->details()->exists()) {
            return;
        }

        $tanggal = $saat->copy()->startOfMonth()->addDays(9);

        if ($dibayar >= $harga) {
            $this->buatDetailPadaTanggal($tagihan, $dibayar, 'Pembayaran LES', $tanggal);

            return;
        }

        // Pembayaran sebagian dipecah jadi dua angsuran supaya riwayatnya hidup.
        $pertama = (int) round($dibayar / 2);
        $kedua = $dibayar - $pertama;

        $this->buatDetailPadaTanggal($tagihan, $pertama, 'Angsuran pertama', $tanggal);

        if ($kedua > 0) {
            $this->buatDetailPadaTanggal($tagihan, $kedua, 'Angsuran kedua', $tanggal->copy()->addDays(6));
        }
    }

    /**
     * created_at/updated_at bukan bagian $fillable PembayaranDetail, jadi
     * tanggal setoran dibekukan lewat forceFill setelah baris dibuat.
     */
    private function buatDetailPadaTanggal(Pembayaran $tagihan, int $nominal, string $keterangan, Carbon $tanggal): void
    {
        $detail = PembayaranDetail::create([
            'id_pembayaran' => $tagihan->id,
            'pembayaran' => $nominal,
            'keterangan' => $keterangan,
        ]);

        $detail->forceFill(['created_at' => $tanggal, 'updated_at' => $tanggal])->save();
    }

    private function catatanSiswa(array $siswa): void
    {
        $catatan = [
            'Nayla Kirana Putri' => 'Minta tambahan latihan soal cerita.',
            'Farrel Adityo Nugroho' => 'Persiapan ujian sekolah, fokus aljabar.',
            'Kenzie Alvaro Pratama' => 'Masih perlu pendampingan menulis huruf sambung.',
            'Zahra Aulia Rahmadani' => 'Target masuk sekolah favorit, tambah tryout.',
        ];

        foreach ($catatan as $nama => $isi) {
            if (! isset($siswa[$nama])) {
                continue;
            }

            Tanda::firstOrCreate(
                ['siswa_id' => $siswa[$nama]->id],
                ['keterangan' => $isi]
            );
        }
    }

    private function siswaArsip($paket): void
    {
        $arsip = [
            ['Gilang Ramadhan Saputra', 'Gilang', '9', '+6281234500011', 'SMP Matematika 2X/Minggu'],
            ['Sabrina Aulia Kusuma', 'Sabrina', '6', '+6281234500012', 'Persiapan TKA 3X/Minggu'],
            ['Bagas Wicaksono', 'Bagas', '5', '+6281234500013', 'SD All Mapel 3X/Minggu'],
        ];

        foreach ($arsip as [$nama, $panggilan, $kelas, $noHp, $namaPaket]) {
            Arsip::firstOrCreate(
                ['name' => $nama],
                [
                    'panggilan' => $panggilan,
                    'kelas' => $kelas,
                    'no_hp' => $noHp,
                    'paket_pembayaran' => $paket[$namaPaket]->id ?? null,
                ]
            );
        }
    }

    private function ringkasan(): void
    {
        $this->command?->newLine();
        $this->command?->info('Data demo siap.');
        $this->command?->line('  Siswa aktif      : '.Siswa::count());
        $this->command?->line('  Siswa diarsipkan : '.Arsip::count());
        $this->command?->line('  Baris jadwal     : '.Jadwal::count());
        $this->command?->line('  Paket            : '.Paket::count());
        $this->command?->line('  Tagihan          : '.Pembayaran::count());
        $this->command?->line('    - lunas        : '.Pembayaran::where('status', 2)->count());
        $this->command?->line('    - dicicil      : '.Pembayaran::where('status', 1)->count());
        $this->command?->line('    - belum bayar  : '.Pembayaran::where('status', 0)->count());
        $this->command?->line('  Setoran tercatat : '.PembayaranDetail::count());
        $this->command?->line('  Aturan diskon    : '.Diskon::count());
        $this->command?->newLine();
        $this->command?->line('Login admin: admin@example.com / 12345678');
    }
}
