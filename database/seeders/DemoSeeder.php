<?php

namespace Database\Seeders;

use App\Models\AbsensiGuru;
use App\Models\Arsip;
use App\Models\AspekPenilaian;
use App\Models\BatchPembayaranLog;
use App\Models\Diskon;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\JadwalTeksLog;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\NilaiAspek;
use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Penggajian;
use App\Models\Pertemuan;
use App\Models\RaporCetak;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\StashPemulihanLog;
use App\Models\Tanda;
use App\Models\TingkatKemampuan;
use App\Models\User;
use App\Services\IrisanSesiService;
use App\Services\PayrollService;
use App\Services\StashJadwalService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Menyiapkan data demo E-Ling Course...');

        $this->callSilent(RoleSeeder::class);
        $this->akunAdmin();

        $master = $this->masterData();
        $siswa = $this->siswaDanKeluarga($master['paket']);
        $this->jadwalKelas($master, $siswa);
        $this->diskonKeluarga($siswa);
        $this->riwayatPembayaran($siswa, $master['paket']);
        $this->catatanSiswa($siswa);
        $this->siswaBelumLengkap($master['paket']);
        $this->siswaArsip($master['paket']);
        $this->tingkatKemampuan();
        $this->akunDanTarifGuru();
        $this->aspekPenilaian();
        $this->modulAjarDanKehadiran();
        $this->alurMengajarHidup();
        $this->aspekYangSudahDipensiunkan();
        $this->contohPenggajian();
        $this->tagihanDiLuarPaket($siswa);
        $this->materiDiulang();
        $this->celahGuruBerhalangan();
        $this->riwayatCetakRapor();
        $this->jejakOperasional();

        $this->ringkasan();
    }

    private function akunAdmin(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('12345678')]
        );

        if (! $admin->hasRole('admin')) {
            $admin->syncRoles(['admin']);
        }

        return $admin;
    }

    private function masterData(): array
    {
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $nama) {
            Hari::firstOrCreate(['name' => $nama]);
        }

        $sesiData = [
            ['Sesi 1', '13:00', '14:00'],
            ['Sesi 1.30', '13:30', '14:30'],
            ['Sesi 2', '14:00', '15:00'],
            ['Sesi 3', '15:30', '16:30'],
            ['Sesi 4', '16:30', '17:30'],
            ['Sesi 5', '18:30', '20:00'],
        ];
        foreach ($sesiData as [$nama, $mulai, $selesai]) {
            Sesi::firstOrCreate(['name' => $nama], ['start_time' => $mulai, 'end_time' => $selesai]);
        }

        $guru = collect(['Bu Rina', 'Bu Sekar', 'Pak Anwar', 'Bu Melati', 'Pak Bagas', 'Bu Kirana', 'Pak Yusuf'])
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

    private function siswaDanKeluarga($paket): array
    {
        $keluarga = [
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
        $irisan = app(IrisanSesiService::class);

        foreach ($kelas as [$hari, $sesi, $mapel, $guru, $ruang, $daftarSiswa]) {
            $hariId = Hari::where('name', $hari)->value('id');
            $sesiId = Sesi::where('name', $sesi)->value('id');
            $sesiBentrok = $irisan->idBeririsan($sesiId);

            $bentrok = false;
            foreach ($sesiBentrok as $sid) {
                $slotCek = $hariId.'-'.$sid;
                if (isset($terpakai['guru'][$slotCek.'-'.$master['guru'][$guru]->id])
                    || isset($terpakai['ruang'][$slotCek.'-'.$master['ruang'][$ruang]->id])) {
                    $bentrok = true;
                    break;
                }
            }

            if ($bentrok) {
                $this->command?->warn("Lewati kelas bentrok: {$hari} {$sesi} {$mapel}");

                continue;
            }

            $slot = $hariId.'-'.$sesiId;
            $terpakai['guru'][$slot.'-'.$master['guru'][$guru]->id] = true;
            $terpakai['ruang'][$slot.'-'.$master['ruang'][$ruang]->id] = true;

            $kodeKelas = (string) Str::uuid();

            foreach ($daftarSiswa as $nama) {
                if (! isset($siswa[$nama])) {
                    continue;
                }

                $siswaBentrok = false;
                foreach ($sesiBentrok as $sid) {
                    if (isset($terpakai['siswa'][$hariId.'-'.$sid.'-'.$siswa[$nama]->id])) {
                        $siswaBentrok = true;
                        break;
                    }
                }
                if ($siswaBentrok) {
                    continue;
                }
                $terpakai['siswa'][$slot.'-'.$siswa[$nama]->id] = true;

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
        Diskon::firstOrCreate(
            ['no_hp' => '+6281234500002'],
            ['diskon' => 50000, 'keterangan' => 'Diskon kakak beradik']
        );
        Diskon::firstOrCreate(
            ['no_hp' => '+6281234500007'],
            ['diskon' => 50000, 'keterangan' => 'Diskon kakak beradik']
        );

        Diskon::firstOrCreate(
            ['no_hp' => null],
            ['diskon' => 25000, 'keterangan' => 'Promo awal semester']
        );
    }

    private function riwayatPembayaran(array $siswa, $paket): void
    {
        $rencana = [
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
                0 => [1, (int) round($harga * 0.5)],
                default => [2, $harga],
            };
        }

        return match ($urutan % 3) {
            0 => [0, 0],
            1 => [1, (int) round($harga * 0.4)],
            default => [2, $harga],
        };
    }

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

        $pertama = (int) round($dibayar / 2);
        $kedua = $dibayar - $pertama;

        $this->buatDetailPadaTanggal($tagihan, $pertama, 'Angsuran pertama', $tanggal);

        if ($kedua > 0) {
            $this->buatDetailPadaTanggal($tagihan, $kedua, 'Angsuran kedua', $tanggal->copy()->addDays(6));
        }
    }

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

            $tanda = Tanda::firstOrCreate(
                ['siswa_id' => $siswa[$nama]->id],
                ['keterangan' => $isi]
            );

            if ($nama === 'Kenzie Alvaro Pratama') {
                $lama = Carbon::now()->subDays(24);
                $tanda->forceFill(['created_at' => $lama, 'updated_at' => $lama])->save();
            }
        }
    }

    private function siswaBelumLengkap($paket): void
    {
        Siswa::firstOrCreate(
            ['name' => 'Alesha Naura Syifa'],
            [
                'panggilan' => 'Alesha',
                'kelas' => '3',
                'no_hp' => '+6281234500014',
                'paket_pembayaran' => $paket['SD English Class 2X/Minggu']->id ?? null,
            ]
        );

        $rangkap = Siswa::where('name', 'Farrel Adityo Nugroho')->first();
        if ($rangkap) {
            $rangkap->update([
                'paket_pembayaran_3' => $paket['Privat Intensif 1X/Minggu']->id ?? null,
            ]);
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

    private function tingkatKemampuan(): void
    {
        $level = [
            1 => 'Dasar - baru mengenal materi',
            2 => 'Berkembang - sudah paham konsep awal',
            3 => 'Mahir - lancar mengerjakan sendiri',
            4 => 'Mandiri - siap materi pengayaan',
        ];

        $dibuat = [];
        foreach ($level as $angka => $keterangan) {
            $dibuat[$angka] = TingkatKemampuan::firstOrCreate(['level' => $angka], ['keterangan' => $keterangan]);
        }

        $urutan = 1;
        foreach (Siswa::orderBy('id')->get() as $s) {
            $s->update(['tingkat_kemampuan_id' => $dibuat[($urutan % 4) + 1]->id]);
            $urutan++;
        }
    }

    private function akunDanTarifGuru(): void
    {
        $tarif = [
            'Bu Rina' => [1200000, 60000],
            'Bu Sekar' => [1000000, 50000],
            'Pak Anwar' => [1500000, 75000],
            'Bu Melati' => [900000, 45000],
            'Pak Bagas' => [1100000, 55000],
            'Bu Kirana' => [800000, 40000],
        ];

        foreach ($tarif as $nama => [$bawaan, $perHadir]) {
            $guru = Guru::where('name', $nama)->first();
            if (! $guru) {
                continue;
            }

            $email = Str::slug($nama, '.').'@eling.test';
            $guru->update([
                'email' => $email,
                'gaji_bawaan' => $bawaan,
                'tunjangan_fungsional' => $nama === 'Bu Rina' ? 350_000 : 0,
                'potongan' => $nama === 'Pak Anwar' ? 75_000 : 0,
                'gaji_per_kehadiran' => $perHadir,
            ]);

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $nama, 'password' => bcrypt('guru12345')]
            );
            $user->update(['guru_id' => $guru->id]);
            $user->syncRoles(['guru']);
        }
    }

    private function aspekPenilaian(): void
    {
        $daftar = [
            ['Vocabulary Mastery', 'Mengenal dan menggunakan vocabulary sesuai materi level'],
            ['Speaking Confidence', 'Keberanian berbicara menggunakan English'],
            ['Pronunciation', 'Kejelasan pengucapan words & expressions'],
            ['Listening Response', 'Memahami dan merespon instruksi sederhana'],
            ['Conversation Skills', 'Kemampuan menjawab dan melakukan simple conversation'],
            ['Participation', 'Keaktifan selama pembelajaran'],
        ];

        foreach ($daftar as $urutan => [$nama, $indikator]) {
            AspekPenilaian::firstOrCreate(
                ['nama' => $nama],
                ['indikator' => $indikator, 'urutan' => $urutan + 1, 'aktif' => true]
            );
        }
    }

    private function modulAjarDanKehadiran(): void
    {
        $materi = ['Pengantar & diagnostik', 'Latihan terbimbing', 'Latihan mandiri', 'Ulangan harian'];

        foreach (Jadwal::whereNotNull('kode_kelas')->get()->groupBy('kode_kelas') as $kodeKelas => $baris) {
            $pertama = $baris->first();

            $modul = ModulAjar::firstOrCreate(['kode_kelas' => $kodeKelas], [
                'tujuan_pembelajaran' => 'Siswa mampu menyelesaikan soal tingkat dasar secara mandiri.',
                'kompetensi_awal' => 'Sudah mengenal konsep dasar dari sekolah.',
                'model_pembelajaran' => 'Diskusi, latihan terbimbing, lalu latihan mandiri.',
                'sarana_media' => 'Papan tulis, modul cetak, lembar kerja.',
            ]);

            $jumlahDiajar = ((int) $pertama->id % 3) + 1;

            foreach ($materi as $i => $judul) {
                $sudahDiajar = $i < $jumlahDiajar;

                $detail = ModulAjarDetail::firstOrCreate(
                    ['modul_ajar_id' => $modul->id, 'materi' => $judul],
                    [
                        'sub_materi' => 'Bagian '.($i + 1),
                        'cara_mengajar' => 'Penjelasan singkat lalu latihan soal.',
                    ]
                );

                if (! $sudahDiajar) {
                    continue;
                }

                $pertemuan = Pertemuan::firstOrCreate(
                    ['modul_ajar_detail_id' => $detail->id, 'tanggal' => now()->subDays((4 - $i) * 3)->toDateString()],
                    ['guru_id' => $pertama->guru_id, 'selesai_pada' => now()->subDays((4 - $i) * 3)]
                );

                $aspeks = AspekPenilaian::aktif()->orderBy('urutan')->get();

                foreach ($baris as $j) {
                    $hadir = ($j->siswa_id + $i) % 7 !== 0;

                    $absensi = ModulAjarAbsensi::firstOrCreate(
                        ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $j->siswa_id],
                        ['hadir' => $hadir, 'nilai' => null]
                    );

                    if (! $hadir) {
                        continue;
                    }

                    foreach ($aspeks as $urutan => $aspek) {
                        NilaiAspek::firstOrCreate(
                            ['modul_ajar_absensi_id' => $absensi->id, 'aspek_penilaian_id' => $aspek->id],
                            ['skor' => 2 + (($j->siswa_id + $i + $urutan) % 4)]
                        );
                    }
                }

                AbsensiGuru::firstOrCreate(
                    ['pertemuan_id' => $pertemuan->id],
                    ['guru_id' => $pertama->guru_id, 'tanggal' => $pertemuan->tanggal->toDateString()]
                );
            }
        }
    }

    private function contohPenggajian(): void
    {
        $guru = Guru::where('name', 'Bu Kirana')->first();
        if (! $guru) {
            return;
        }

        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
        $payroll = app(PayrollService::class);

        if (Penggajian::count() > 0) {
            return;
        }

        try {
            $payroll->jalankan($guru, $admin);
        } catch (\RuntimeException $e) {
            $this->command?->warn('Lewati contoh penggajian: '.$e->getMessage());

            return;
        }

        $guruDibatalkan = Guru::where('name', 'Bu Melati')->first();
        if ($guruDibatalkan) {
            try {
                $struk = $payroll->jalankan($guruDibatalkan, $admin);
                $struk->forceFill(['dijalankan_pada' => Carbon::now()->subDays(20)])->save();
                $payroll->batalkan($struk, $admin, 'Tarif per kehadiran keliru, diterbitkan ulang.');
            } catch (\RuntimeException $e) {
                $this->command?->warn('Lewati contoh pembatalan struk: '.$e->getMessage());
            }
        }

        $guruLama = Guru::where('name', 'Bu Sekar')->first();
        if ($guruLama) {
            try {
                $strukLama = $payroll->jalankan($guruLama, $admin);
                $strukLama->forceFill([
                    'dijalankan_pada' => Carbon::now()->subMonth()->startOfMonth()->addDays(4),
                ])->save();
            } catch (\RuntimeException $e) {
                $this->command?->warn('Lewati contoh struk lama: '.$e->getMessage());
            }
        }
    }

    private function alurMengajarHidup(): void
    {
        $kelas = ModulAjar::with('details.pertemuans')->orderBy('id')->get()
            ->filter(fn (ModulAjar $m) => $m->details->isNotEmpty())
            ->values();

        if ($kelas->count() < 4) {
            return;
        }

        $belumDiajar = fn (ModulAjar $m) => $m->details->first(fn ($d) => $d->pertemuans->isEmpty());
        $sudahBerjalan = fn (ModulAjar $m) => $m->details->contains(
            fn ($d) => $d->pertemuans->contains(fn (Pertemuan $p) => $p->selesai_pada === null)
        );

        if (! $sudahBerjalan($kelas[0]) && ($d = $belumDiajar($kelas[0]))) {
            Pertemuan::firstOrCreate(
                ['modul_ajar_detail_id' => $d->id, 'tanggal' => now()->toDateString()],
                ['guru_id' => $this->guruKelas($kelas[0])]
            );
        }

        if ($d = $belumDiajar($kelas[1])) {
            $d->update(['tidak_bisa_hadir' => true]);
        }

        $pengganti = Guru::where('name', 'Pak Bagas')->first();

        if ($pengganti && ! $sudahBerjalan($kelas[2]) && ($d = $belumDiajar($kelas[2]))) {
            Pertemuan::firstOrCreate(
                ['modul_ajar_detail_id' => $d->id, 'tanggal' => now()->toDateString()],
                ['guru_id' => $pengganti->id, 'guru_pengganti_id' => $pengganti->id]
            );
        }

        $sudahDiajar = $kelas[3]->details
            ->flatMap(fn ($d) => $d->pertemuans)
            ->first(fn (Pertemuan $p) => $p->selesai_pada !== null);

        if ($pengganti && $sudahDiajar) {
            $sudahDiajar->update(['guru_id' => $pengganti->id, 'guru_pengganti_id' => $pengganti->id]);
            AbsensiGuru::where('pertemuan_id', $sudahDiajar->id)->update(['guru_id' => $pengganti->id]);
        }
    }

    private function guruKelas(ModulAjar $modul): ?int
    {
        return Jadwal::where('kode_kelas', $modul->kode_kelas)->value('guru_id');
    }

    private function aspekYangSudahDipensiunkan(): void
    {
        $aspek = AspekPenilaian::firstOrCreate(
            ['nama' => 'Reading Fluency'],
            ['indikator' => 'Kelancaran membaca teks pendek', 'urutan' => 99, 'aktif' => false]
        );

        $absensis = ModulAjarAbsensi::where('hadir', true)->orderBy('id')->limit(12)->get();

        foreach ($absensis as $i => $absensi) {
            NilaiAspek::firstOrCreate(
                ['modul_ajar_absensi_id' => $absensi->id, 'aspek_penilaian_id' => $aspek->id],
                ['skor' => 2 + ($i % 4)]
            );
        }
    }

    private function tagihanDiLuarPaket(array $siswa): void
    {
        $tambahan = [
            ['Nayla Kirana Putri', 'Buku modul semester ganjil', 85000, true],
            ['Farrel Adityo Nugroho', 'Biaya tryout gabungan', 120000, false],
            ['Zahra Aulia Rahmadani', 'Denda buku terlambat', 25000, true],
        ];

        foreach ($tambahan as [$nama, $keterangan, $harga, $lunas]) {
            if (! isset($siswa[$nama])) {
                continue;
            }

            $s = $siswa[$nama];
            $saat = Carbon::now()->subDays(9);

            $tagihan = Pembayaran::firstOrCreate(
                ['id_siswa' => $s->id, 'keterangan' => $keterangan, 'id_paket' => null],
                [
                    'no_hp' => $s->no_hp,
                    'harga' => $harga,
                    'total_sudah_dibayar' => $lunas ? $harga : 0,
                    'status' => $lunas ? 2 : 0,
                    'periode' => null,
                ]
            );

            $tagihan->forceFill(['created_at' => $saat, 'updated_at' => $saat])->save();

            if ($lunas && $tagihan->details()->count() === 0) {
                $this->buatDetailPadaTanggal($tagihan, $harga, 'Pelunasan '.$keterangan, $saat);
            }
        }
    }

    private function materiDiulang(): void
    {
        $aspeks = AspekPenilaian::aktif()->orderBy('urutan')->get();

        if ($aspeks->isEmpty()) {
            return;
        }

        $kandidat = Pertemuan::whereNotNull('selesai_pada')
            ->with('modulAjarDetail.modulAjar')
            ->orderBy('id')
            ->get()
            ->unique('modul_ajar_detail_id')
            ->take(4);

        foreach ($kandidat as $urutan => $awal) {
            $kode = $awal->modulAjarDetail?->modulAjar?->kode_kelas;
            if (! $kode) {
                continue;
            }

            $tanggal = $awal->tanggal->copy()->addDays(7);

            $ulang = Pertemuan::firstOrCreate(
                ['modul_ajar_detail_id' => $awal->modul_ajar_detail_id, 'tanggal' => $tanggal->toDateString()],
                ['guru_id' => $awal->guru_id, 'selesai_pada' => $tanggal]
            );

            if (! $ulang->wasRecentlyCreated) {
                continue;
            }

            foreach (Jadwal::where('kode_kelas', $kode)->pluck('siswa_id')->unique() as $siswaId) {
                $absensi = ModulAjarAbsensi::firstOrCreate(
                    ['pertemuan_id' => $ulang->id, 'siswa_id' => $siswaId],
                    ['hadir' => true, 'nilai' => null]
                );

                foreach ($aspeks as $i => $aspek) {
                    NilaiAspek::firstOrCreate(
                        ['modul_ajar_absensi_id' => $absensi->id, 'aspek_penilaian_id' => $aspek->id],
                        ['skor' => min(5, 3 + (($siswaId + $i + $urutan) % 3))]
                    );
                }
            }

            AbsensiGuru::firstOrCreate(
                ['pertemuan_id' => $ulang->id],
                ['guru_id' => $awal->guru_id, 'tanggal' => $tanggal->toDateString()]
            );
        }
    }

    private function celahGuruBerhalangan(): void
    {
        $aspeks = AspekPenilaian::aktif()->orderBy('urutan')->get();

        if ($aspeks->isEmpty()) {
            return;
        }

        $dipakai = $this->kisahTigaMingguBerhalangan($aspeks);
        $this->slotTerbukaUntukAjarUlang($dipakai);
    }

    private function kisahTigaMingguBerhalangan(Collection $aspeks): ?int
    {
        $detail = ModulAjarDetail::with('modulAjar:id,kode_kelas')->orderByDesc('id')->first();
        $kode = $detail?->modulAjar?->kode_kelas;

        if (! $kode) {
            return null;
        }

        $pemilik = $this->guruKelas($detail->modulAjar);
        $pengganti = Guru::whereNotNull('email')->where('id', '!=', $pemilik)->orderBy('id')->value('id');
        $siswaIds = Jadwal::where('kode_kelas', $kode)->pluck('siswa_id')->unique();

        if (! $pemilik || ! $pengganti || $siswaIds->isEmpty()) {
            return null;
        }

        $babak = [
            ['minggu' => 3, 'guru' => $pengganti, 'pengganti' => $pengganti, 'skor' => 3],
            ['minggu' => 2, 'guru' => $pengganti, 'pengganti' => $pengganti, 'skor' => 4],
            ['minggu' => 1, 'guru' => $pemilik, 'pengganti' => null, 'skor' => 5],
        ];

        foreach ($babak as $b) {
            $tanggal = now()->copy()->subWeeks($b['minggu'])->startOfDay();

            $pertemuan = Pertemuan::firstOrCreate(
                ['modul_ajar_detail_id' => $detail->id, 'tanggal' => $tanggal->toDateString()],
                ['guru_id' => $b['guru'], 'guru_pengganti_id' => $b['pengganti'], 'selesai_pada' => $tanggal]
            );

            AbsensiGuru::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id],
                ['guru_id' => $b['pengganti'] ?: $b['guru'], 'tanggal' => $tanggal->toDateString()]
            );

            foreach ($siswaIds as $siswaId) {
                $absensi = ModulAjarAbsensi::firstOrCreate(
                    ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                    ['hadir' => true, 'nilai' => null]
                );

                foreach ($aspeks as $aspek) {
                    NilaiAspek::firstOrCreate(
                        ['modul_ajar_absensi_id' => $absensi->id, 'aspek_penilaian_id' => $aspek->id],
                        ['skor' => $b['skor']]
                    );
                }
            }
        }

        return $detail->id;
    }

    private function slotTerbukaUntukAjarUlang(?int $kecuali): void
    {
        $sudahAda = ModulAjarDetail::where('tidak_bisa_hadir', true)
            ->whereHas('pertemuans', fn ($q) => $q->whereNotNull('selesai_pada'))
            ->exists();

        if ($sudahAda) {
            return;
        }

        $detail = ModulAjarDetail::where('tidak_bisa_hadir', false)
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->whereHas('pertemuans', fn ($q) => $q->whereNotNull('selesai_pada'))
            ->whereDoesntHave('pertemuans', fn ($q) => $q->whereNull('selesai_pada'))
            ->orderBy('id')
            ->first();

        if (! $detail) {
            return;
        }

        $detail->update(['tidak_bisa_hadir' => true]);
        ModulAjarDetail::where('id', $detail->id)->update(['updated_at' => now()->subDays(3)]);
    }

    private function riwayatCetakRapor(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        $catatan = [
            ['Berani memulai percakapan tanpa diminta dan hafal kosakata tema keluarga.',
                'Pengucapan bunyi th dan v masih tertukar, perlu latihan menirukan.',
                'Perkembangannya konsisten. Mohon dibiasakan menyapa dengan English di rumah.',
                'Bulan depan masuk ke tema hobi dan latihan percakapan dua arah.'],
            ['Sangat teliti mengerjakan lembar kerja dan selalu menyelesaikan tugas.',
                'Masih malu menjawab kalau ditanya mendadak di depan kelas.',
                'Sudah ada kemajuan besar di bagian menulis. Terima kasih atas dukungannya.',
                'Akan ditambah porsi speaking berpasangan supaya lebih percaya diri.'],
            ['Cepat menangkap instruksi dan sering membantu teman sekelas.',
                'Perlu konsisten mengerjakan PR agar kosakata barunya tidak cepat lupa.',
                'Anak yang menyenangkan di kelas. Tinggal dijaga kebiasaan belajarnya.',
                'Mulai latihan membaca cerita pendek dan menceritakan ulang.'],
        ];

        $siswaDinilai = ModulAjarAbsensi::where('hadir', true)
            ->get()
            ->groupBy('siswa_id')
            ->sortByDesc(fn ($rows) => $rows->count())
            ->take(3);

        $urutan = 0;
        foreach ($siswaDinilai as $siswaId => $absensis) {
            $isi = $catatan[$urutan % count($catatan)];
            $pertemuanIds = $absensis->pluck('pertemuan_id')->filter()->unique()->values();

            if ($pertemuanIds->isEmpty()) {
                continue;
            }

            $sudahAda = RaporCetak::where('siswa_id', $siswaId)->exists();
            if ($sudahAda) {
                continue;
            }

            $saat = Carbon::now()->subDays(6 - $urutan);

            RaporCetak::create([
                'siswa_id' => $siswaId,
                'dicetak_oleh' => $admin?->id,
                'periode_label' => $saat->translatedFormat('F Y'),
                'pertemuan_ids' => $pertemuanIds->all(),
                'kekuatan' => $isi[0],
                'perbaikan' => $isi[1],
                'komentar' => $isi[2],
                'rencana' => $isi[3],
            ])->forceFill(['created_at' => $saat, 'updated_at' => $saat])->save();

            $urutan++;
        }
    }

    private function jejakOperasional(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        BatchPembayaranLog::firstOrCreate(
            ['jenis' => 'penagihan_massal', 'periode' => Carbon::now()->subMonth()->format('Y-m')],
            ['jumlah_diproses' => Pembayaran::whereNotNull('id_paket')->count(), 'user_id' => $admin?->id]
        );

        if (JadwalTeksLog::count() === 0) {
            foreach ([2, 9, 16] as $mundur) {
                $saat = Carbon::now()->subDays($mundur);
                JadwalTeksLog::create(['user_id' => $admin?->id])
                    ->forceFill(['created_at' => $saat, 'updated_at' => $saat])->save();
            }
        }

        if (StashPemulihanLog::count() === 0) {
            $stash = app(StashJadwalService::class);
            $isi = $stash->encode($stash->bungkus());
            $saat = Carbon::now()->subDays(5);

            StashPemulihanLog::create([
                'dipulihkan_oleh' => $admin?->id,
                'jumlah_sebelum' => Jadwal::count(),
                'jumlah_sesudah' => Jadwal::count(),
                'bentrok_masuk' => 0,
                'isi_sebelum' => $isi,
            ])->forceFill(['created_at' => $saat, 'updated_at' => $saat])->save();
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
        $this->command?->line('  Tingkat kemampuan: '.TingkatKemampuan::count());
        $this->command?->line('  Modul ajar       : '.ModulAjar::count().' kelas, '.ModulAjarDetail::count().' materi');
        $this->command?->line('  Nilai siswa      : '.ModulAjarAbsensi::count().' ('.NilaiAspek::count().' skor aspek dari '.AspekPenilaian::count().' aspek)');
        $this->command?->line('  Kehadiran guru   : '.AbsensiGuru::count());
        $this->command?->line('  Struk penggajian : '.Penggajian::count().' ('.Penggajian::whereNotNull('dibatalkan_pada')->count().' dibatalkan)');
        $this->command?->line('  Guru tanpa akun  : '.Guru::whereNull('email')->count().' dari '.Guru::count());
        $this->command?->line('  Pertemuan        : '.Pertemuan::count().' ('.Pertemuan::whereNull('selesai_pada')->count().' sedang berlangsung)');
        $this->command?->line('  Slot terbuka     : '.ModulAjarDetail::where('tidak_bisa_hadir', true)->count()
            .' ('.ModulAjarDetail::where('tidak_bisa_hadir', true)->whereHas('pertemuans', fn ($q) => $q->whereNotNull('selesai_pada'))->count().' di antaranya sesi ajar ulang)');
        $this->command?->line('  Dipegang pengganti: '.Pertemuan::berlangsung()->whereNotNull('guru_pengganti_id')->count().' kelas sedang diajar guru pengganti');
        $this->command?->line('  Materi diulang   : '.Pertemuan::selesai()->get()->groupBy('modul_ajar_detail_id')
            ->filter(fn ($p) => $p->count() > 1)->count().' materi diajar lebih dari sekali');
        $this->command?->line('  Rapor dicetak    : '.RaporCetak::count().' kali');
        $this->command?->newLine();
        $this->command?->line('Login admin: admin@example.com / 12345678');
        $this->command?->line('Login guru : bu.rina@eling.test / guru12345');
    }
}
