<?php

namespace App\Support;

class PusatBantuan
{
    /**
     * @return array{title: string, summary: string, sections: array<int, array{title: string, items: array<int, string>}>}
     */
    public static function untuk(?string $namaRute, string $tab = ''): array
    {
        $kunci = $namaRute === 'dashboard' && $tab !== ''
            ? 'dashboard.'.$tab
            : (string) $namaRute;

        return self::semua()[$kunci] ?? self::bawaan();
    }

    /**
     * @return array<string, array{title: string, summary: string, sections: array<int, array{title: string, items: array<int, string>}>}>
     */
    public static function semua(): array
    {
        return [
            'dashboard.jadwal' => [
                'title' => 'Panduan Jadwal Pelajaran',
                'summary' => 'Papan kanban tempat menyusun kelas. Satu kartu adalah satu kelas berisi beberapa siswa, diletakkan pada kotak hari dan sesi. Sistem menolak sendiri jadwal yang bertabrakan, jadi fokus Anda cukup pada menyusunnya.',
                'sections' => [
                    [
                        'title' => 'Cara Menambah Kelas',
                        'items' => [
                            'Tekan tombol tambah pada kotak hari dan sesi yang diinginkan, lalu pilih mata pelajaran, guru, ruang, dan siswa yang ikut.',
                            'Satu kartu boleh berisi banyak siswa. Semua siswa di kartu itu dianggap satu kelas yang sama.',
                            'Kalau mata pelajaran, guru, ruang, atau sesi belum ada, tambahkan dulu lewat menu Workshop.',
                        ],
                    ],
                    [
                        'title' => 'Memindahkan dan Mengubah',
                        'items' => [
                            'Seret kartu ke kotak lain untuk memindahkan jadwalnya. Modul Ajar kelas itu ikut berpindah, tidak hilang.',
                            'Klik kartu untuk mengubah isinya: ganti guru, ganti ruang, tambah atau kurangi siswa.',
                            'Kotak pencarian di atas menyaring hari, sesi, mapel, guru, ruang, dan nama siswa sekaligus.',
                        ],
                    ],
                    [
                        'title' => 'Kenapa Jadwal Bisa Ditolak',
                        'items' => [
                            'Sesi di sini saling bertindih jamnya. Sesi 13:00 sampai 14:00 beririsan dengan sesi 13:30 sampai 14:30.',
                            'Ruang, guru, dan siswa yang terpakai pada satu sesi otomatis dianggap terpakai juga pada sesi yang jamnya bertindih.',
                            'Kalau ditolak, pesannya menyebut sesi mana yang bertabrakan, jadi Anda tahu persis apa yang perlu digeser.',
                            'Sesi yang hanya bersentuhan di ujung boleh berbagi ruang. Misalnya 13:00 sampai 14:00 lalu 14:00 sampai 15:00, itu tidak dianggap bentrok.',
                        ],
                    ],
                    [
                        'title' => 'Export dan Cadangan',
                        'items' => [
                            'Export PDF mencetak jadwal sesuai yang sedang tampil. Copy Teks menyiapkan format siap tempel ke WhatsApp.',
                            'Sebelum perombakan besar, tekan Download Stash untuk menyimpan kondisi jadwal saat ini sebagai berkas cadangan.',
                            'Memulihkan stash mengganti seluruh jadwal. Kondisi sebelumnya otomatis diarsipkan dan tautan unduhnya muncul di pesan sukses, jadi salah berkas masih bisa dibatalkan.',
                        ],
                    ],
                ],
            ],

            'dashboard.data_siswa' => [
                'title' => 'Panduan Data Siswa',
                'summary' => 'Daftar seluruh siswa aktif dan arsip. Di sini Anda menyaring data, memeriksa kelengkapan jadwal, menambah catatan, mengarsipkan, dan mengekspor ke Excel.',
                'sections' => [
                    [
                        'title' => 'Cara Memakai Filter',
                        'items' => [
                            'Keenam filter berbentuk daftar centang. Klik kotaknya, lalu centang satu atau beberapa pilihan sekaligus.',
                            'Mencentang dua pilihan berarti salah satu. Mencentang Sesi 1 dan Sesi 2 akan menampilkan siswa yang ikut salah satu dari keduanya.',
                            'Setiap daftar punya kotak pencarian. Tombol Pilih semua tampil hanya mencentang yang sedang terlihat setelah dicari.',
                            'Baris Filter aktif di bawah menunjukkan filter mana saja yang sedang menyala. Tombol Reset mengosongkan semuanya sekaligus.',
                        ],
                    ],
                    [
                        'title' => 'Membaca Status Siswa',
                        'items' => [
                            'Tulisan 3 dari 2 Pertemuan berarti siswa terjadwal 3 kali padahal kuota paketnya 2.',
                            'Angka kuota dijumlahkan dari semua paket yang dimiliki siswa itu, bukan hanya paket pertamanya.',
                            'Nama berwarna kuning berarti jadwalnya belum memenuhi kuota paket.',
                            'Titik kuning pada tombol info berarti siswa itu punya catatan.',
                        ],
                    ],
                    [
                        'title' => 'Arsip dan Export',
                        'items' => [
                            'Mengarsipkan siswa memindahkannya ke tab Arsip berikut jadwalnya, bukan menghapusnya. Dari Arsip bisa dipulihkan lagi.',
                            'Hapus permanen hanya ada di tab Arsip dan tidak bisa dibatalkan.',
                            'Export Excel mengikuti filter yang sedang aktif. Angka pada tombolnya menunjukkan berapa siswa yang akan terbawa.',
                            'Menambah atau mengubah data siswa dilakukan di menu Workshop, bukan di sini.',
                        ],
                    ],
                ],
            ],

            'dashboard.pembayaran' => [
                'title' => 'Panduan Pembayaran',
                'summary' => 'Tempat membuat tagihan, mencatat uang masuk, memberi diskon, dan mencetak bukti. Ini bagian yang menyangkut uang, jadi setiap pencatatan menambah riwayat baru dan tidak pernah menimpa yang lama.',
                'sections' => [
                    [
                        'title' => 'Membuat Tagihan',
                        'items' => [
                            'Tagihan membuat satu invoice untuk satu siswa. Penagihan Massal membuat tagihan bulanan untuk semua siswa sesuai paketnya.',
                            'Penagihan Massal aman ditekan dua kali. Sistem menolak membuat tagihan paket yang sama untuk bulan yang sama.',
                            'Tagihan di luar paket seperti buku, denda, atau kegiatan dibuat lewat Tagihan biasa dan memang boleh berulang.',
                        ],
                    ],
                    [
                        'title' => 'Mencatat Pembayaran',
                        'items' => [
                            'Catat Bayar dipakai untuk pelunasan maupun cicilan. Isi nominal sesuai uang yang benar-benar diterima.',
                            'Setiap pencatatan menambah baris riwayat baru, sehingga jejaknya selalu bisa diperiksa kembali.',
                            'Sistem menolak nominal yang melebihi sisa tagihan, dan menolak pengiriman yang sama dua kali dalam 3 menit.',
                            'Jangan menutup halaman saat layar gelap memproses muncul. Itu tanda pencatatan sedang berjalan.',
                        ],
                    ],
                    [
                        'title' => 'Diskon dan Keluarga',
                        'items' => [
                            'Tagihan dikelompokkan per nomor HP, jadi kakak beradik dengan nomor sama muncul sebagai satu keluarga.',
                            'Diskon keluarga dan diskon universal dijumlahkan. Keduanya ikut terhitung di ringkasan dan di struk.',
                            'Kalau saudara belum tergabung, samakan dulu nomor HP-nya lewat menu Workshop.',
                        ],
                    ],
                    [
                        'title' => 'Hal yang Perlu Hati-hati',
                        'items' => [
                            'Tagihan yang sudah punya riwayat pembayaran tidak bisa dipindah ke siswa lain. Kalau salah orang, buat tagihan baru untuk siswa yang benar.',
                            'Harga tagihan tidak bisa diturunkan di bawah uang yang sudah terlanjur dibayarkan.',
                            'Filter bulan dan pencarian ikut terbawa saat mencetak struk atau export, jadi periksa dulu sebelum mencetak.',
                        ],
                    ],
                ],
            ],

            'dashboard.ringkasan' => [
                'title' => 'Panduan Ringkasan',
                'summary' => 'Halaman pemeriksaan harian. Isinya bukan data baru, melainkan hal yang perlu ditindaklanjuti dari tab lain: kelas hari ini, uang yang tertunggak, data yang belum rapi, dan jadwal yang diam-diam bertabrakan.',
                'sections' => [
                    [
                        'title' => 'Yang Ditampilkan',
                        'items' => [
                            'Kelas hari ini diambil dari nama hari yang sedang berjalan, lengkap dengan jam sesinya.',
                            'Okupansi ruang dan beban guru bisa dilihat per hari atau per minggu lewat pilihan periode.',
                            'Pengingat finansial merangkum tagihan yang belum lunas beserta umur piutangnya.',
                        ],
                    ],
                    [
                        'title' => 'Bentrok Tersembunyi',
                        'items' => [
                            'Panel ini menampilkan jadwal yang bertabrakan karena jam sesinya beririsan.',
                            'Jadwal baru tidak akan pernah masuk ke daftar ini karena sudah ditolak di awal. Yang muncul di sini adalah data lama atau hasil pemulihan stash.',
                            'Perbaikannya dilakukan di tab Jadwal Pelajaran dengan memindahkan salah satu kelas.',
                        ],
                    ],
                    [
                        'title' => 'Kebersihan Data',
                        'items' => [
                            'Catatan siswa yang lebih dari 14 hari akan terus muncul sampai catatannya dihapus, karena catatan tidak punya penanda selesai.',
                            'Siswa tanpa jadwal atau tanpa nomor HP ditandai di sini supaya tidak terlewat saat penagihan.',
                        ],
                    ],
                ],
            ],

            'dashboard.payroll' => [
                'title' => 'Panduan Payroll Guru',
                'summary' => 'Menghitung dan menutup gaji guru. Gaji terdiri dari gaji bawaan yang dibayar penuh setiap kali dijalankan, ditambah gaji per kehadiran mengajar yang belum pernah digaji.',
                'sections' => [
                    [
                        'title' => 'Sebelum Menjalankan',
                        'items' => [
                            'Isi dulu Ubah Tarif untuk setiap guru: gaji bawaan dan gaji per kehadiran. Tarif yang masih Rp 0 akan menghasilkan struk Rp 0.',
                            'Angka Kehadiran Belum Digaji berasal dari kelas yang sudah selesai dinilai di menu Absen, bukan dari jumlah jadwal.',
                            'Perkiraan Total Berjalan adalah uang yang belum dibayarkan. Setelah periode ditutup, angka ini kembali ke Rp 0 dengan sendirinya.',
                        ],
                    ],
                    [
                        'title' => 'Menjalankan Penggajian',
                        'items' => [
                            'Siap Lakukan menerbitkan struk untuk satu guru dan menutup semua kehadirannya yang belum digaji. Siap Semua melakukannya untuk semua guru sekaligus.',
                            'Penggajian tidak menghapus apa pun. Kehadiran lama tetap tersimpan dan tetap terhitung pada rekap bulanan guru.',
                            'Tarif disalin ke dalam struk saat diterbitkan. Menaikkan tarif besok tidak akan mengubah struk yang sudah terbit.',
                        ],
                    ],
                    [
                        'title' => 'Kalau Ada yang Salah',
                        'items' => [
                            'Struk tidak bisa diedit. Kalau keliru, batalkan struknya beserta alasannya, lalu jalankan ulang.',
                            'Membatalkan struk melepas kembali kehadirannya sehingga ikut terhitung pada penggajian berikutnya.',
                            'Guru tanpa kehadiran tetap menerima struk berisi gaji bawaan kalau dijalankan, karena gaji bawaan memang dibayar penuh setiap kali.',
                        ],
                    ],
                ],
            ],

            'admin.workshop.index' => [
                'title' => 'Panduan Workshop',
                'summary' => 'Satu-satunya tempat menambah dan mengubah data pokok: mata pelajaran, guru, ruang, sesi waktu, paket, tingkat kemampuan, dan siswa. Setiap form langsung menunjukkan data mirip yang sudah ada, supaya tidak perlu bolak-balik memeriksa halaman lain.',
                'sections' => [
                    [
                        'title' => 'Menambah dan Mengubah Siswa',
                        'items' => [
                            'Satu siswa boleh memegang sampai 5 paket. Slot berikutnya muncul sendiri setelah slot sebelumnya diisi.',
                            'Jumlah pertemuan dari semua paket dijumlahkan menjadi kuota siswa, dan angka itulah yang dipakai di tab Data Siswa.',
                            'Mengetik nomor HP yang sudah dipakai siswa lain akan memunculkan peringatan kemungkinan bersaudara. Untuk penagihan, saudara sebaiknya memakai nomor yang sama.',
                            'Mengetik kelas akan menampilkan kelas mana saja yang sudah berjalan untuk tingkat tersebut.',
                        ],
                    ],
                    [
                        'title' => 'Import Massal Siswa',
                        'items' => [
                            'Tekan Download Kerangka, isi berkasnya, lalu unggah kembali. Nama yang sudah ada akan diperbarui, nama baru akan ditambahkan.',
                            'Kolom Kemampuan diisi angka levelnya saja, misalnya 1. Level yang belum terdaftar akan dilewati tanpa membatalkan barisnya.',
                            'Kolom Nama Paket sampai Nama Paket 5 harus ditulis persis sama dengan nama paket di tab Paket.',
                            'Kolom yang dikosongkan tidak menimpa data lama, jadi aman untuk memperbarui satu kolom saja.',
                        ],
                    ],
                    [
                        'title' => 'Sesi dan Data Pokok Lain',
                        'items' => [
                            'Saat menambah sesi, sistem memperingatkan kalau jamnya bertabrakan dengan sesi lain, menabrak jam istirahat, atau jedanya kurang dari 30 menit.',
                            'Peringatan kemiripan sifatnya informasi saja dan tidak menghalangi penyimpanan.',
                            'Data yang masih dipakai di jadwal tidak bisa dihapus. Pindahkan atau hapus dulu jadwal yang memakainya.',
                            'Menjadwalkan siswa ke kelas tetap dilakukan di tab Jadwal Pelajaran.',
                        ],
                    ],
                    [
                        'title' => 'Slot Kosong',
                        'items' => [
                            'Tab Slot Kosong menunjukkan ruang dan guru yang masih bebas pada setiap kotak hari dan sesi.',
                            'Ruang yang terpakai pada sesi yang jamnya bertindih tidak akan ditawarkan sebagai kosong, walaupun nomor sesinya berbeda.',
                        ],
                    ],
                ],
            ],

            'admin.akunGuru.index' => [
                'title' => 'Panduan Akun Guru',
                'summary' => 'Membuat dan mengelola akun login untuk guru. Halaman ini hanya mengurus email dan akses masuk, bukan data guru itu sendiri.',
                'sections' => [
                    [
                        'title' => 'Membuat Akun',
                        'items' => [
                            'Isi dulu email guru, baru tombol buat akun bisa dipakai.',
                            'Setelah akun jadi, guru bisa masuk untuk melihat jadwalnya, mengisi Modul Ajar, menilai di Absen, dan melihat struk gajinya.',
                            'Sampaikan password awal ke guru yang bersangkutan, lalu minta segera diganti lewat menu Profil.',
                        ],
                    ],
                    [
                        'title' => 'Hal yang Perlu Diketahui',
                        'items' => [
                            'Guru tanpa akun tetap bisa dijadwalkan dan tetap dihitung gajinya seperti biasa.',
                            'Menghapus akun login tidak menghapus data guru maupun jadwalnya.',
                            'Untuk menambah atau mengubah nama guru, gunakan menu Workshop.',
                        ],
                    ],
                ],
            ],

            'modulAjar.index' => [
                'title' => 'Panduan Modul Ajar',
                'summary' => 'Tempat mengisi kurikulum setiap kelas. Satu kelas punya satu modul berisi tujuan pembelajaran, lalu diisi daftar materi yang akan diajarkan satu per satu.',
                'sections' => [
                    [
                        'title' => 'Mengisi Modul',
                        'items' => [
                            'Isi dulu bagian atas modul: tujuan pembelajaran, kompetensi awal, model pembelajaran, dan sarana media. Keempatnya wajib.',
                            'Setelah itu tambahkan materi satu per satu. Hanya kolom materi yang wajib, sisanya boleh menyusul.',
                            'Kartu kelas akan berubah warna dan menampilkan jumlah materi begitu modulnya sudah terisi.',
                        ],
                    ],
                    [
                        'title' => 'Siapa yang Boleh Mengubah',
                        'items' => [
                            'Guru bisa melihat kelasnya sendiri serta mengisi modul dan materi baru.',
                            'Mengubah dan menghapus isi yang sudah tersimpan hanya bisa dilakukan admin.',
                            'Admin melihat seluruh kelas. Guru hanya melihat kelas miliknya sendiri ditambah kelas yang sedang ia gantikan.',
                        ],
                    ],
                    [
                        'title' => 'Kalau Kelas Dipindah',
                        'items' => [
                            'Memindahkan kelas ke hari atau sesi lain tidak memutus modulnya. Modul menempel pada kelas, bukan pada kotak jadwal.',
                            'Mengajar ulang materi yang sudah dinilai akan menimpa nilai sebelumnya, bukan menambah riwayat baru.',
                        ],
                    ],
                ],
            ],

            'absen.index' => [
                'title' => 'Panduan Absen',
                'summary' => 'Papan mengajar harian. Dari sini guru menyiapkan materi, menilai murid, dan menyerahkan kelas kalau berhalangan. Kehadiran mengajar yang tercatat di sini menjadi dasar perhitungan gaji.',
                'sections' => [
                    [
                        'title' => 'Alur Mengajar',
                        'items' => [
                            'Pilih kelas pada kotak hari dan sesi, pilih materi yang akan diajarkan, lalu tekan mulai persiapan. Kartunya berubah kuning.',
                            'Setelah mengajar, lanjut ke penilaian. Centang Hadir untuk murid yang datang, lalu isi nilai 1 sampai 5.',
                            'Tekan Simpan Nilai untuk menyelesaikan pertemuan. Kehadiran mengajar Anda langsung tercatat.',
                        ],
                    ],
                    [
                        'title' => 'Kalau Berhalangan',
                        'items' => [
                            'Tekan Tidak Bisa Hadir pada materi tersebut. Slotnya terbuka untuk semua guru, siapa cepat dia dapat.',
                            'Guru yang mengambil slot itulah yang mengajar dan menilai, dan kehadirannya dihitung untuk dia, bukan untuk guru aslinya.',
                            'Jadwal mingguan tidak ikut berubah. Pergantian ini hanya berlaku untuk satu pertemuan.',
                        ],
                    ],
                    [
                        'title' => 'Hal yang Perlu Diketahui',
                        'items' => [
                            'Kehadiran dihitung per kelas yang selesai dinilai. Mengajar 3 kelas dalam sehari berarti 3 kehadiran, bukan 1.',
                            'Rekap bulanan tetap menampilkan seluruh kelas yang Anda ajar, dan tidak berubah walaupun gajinya sudah dibayarkan.',
                            'Kalau daftar materi masih kosong, isi dulu lewat menu Modul Ajar.',
                        ],
                    ],
                ],
            ],

            'guru.jadwal' => [
                'title' => 'Panduan Portal Guru',
                'summary' => 'Halaman ini menampilkan jadwal mengajar Anda sendiri. Sifatnya hanya untuk dilihat, jadi tidak ada yang bisa tidak sengaja terubah dari sini.',
                'sections' => [
                    [
                        'title' => 'Membaca Jadwal',
                        'items' => [
                            'Setiap kartu adalah satu kelas, lengkap dengan hari, jam sesi, mata pelajaran, ruang, dan daftar muridnya.',
                            'Jadwal ini berulang setiap minggu. Perubahannya diatur admin dari menu Jadwal Pelajaran.',
                        ],
                    ],
                    [
                        'title' => 'Ke Mana Selanjutnya',
                        'items' => [
                            'Untuk mengisi kurikulum kelas, buka menu Modul Ajar.',
                            'Untuk menandai kehadiran dan menilai murid, buka menu Absen.',
                            'Untuk melihat struk gaji Anda, buka menu Gaji.',
                            'Kalau ada jadwal yang keliru, sampaikan ke admin, karena halaman ini tidak bisa mengubah jadwal.',
                        ],
                    ],
                ],
            ],

            'guru.gaji' => [
                'title' => 'Panduan Gaji Guru',
                'summary' => 'Rincian gaji Anda: tarif yang berlaku, kehadiran mengajar yang belum digaji, dan riwayat struk yang sudah terbit.',
                'sections' => [
                    [
                        'title' => 'Membaca Angkanya',
                        'items' => [
                            'Gaji bawaan dibayarkan penuh setiap kali penggajian dijalankan, tidak dipotong berdasarkan jumlah mengajar.',
                            'Gaji per kehadiran dikalikan jumlah kelas yang sudah Anda selesaikan penilaiannya di menu Absen.',
                            'Perkiraan berjalan adalah yang belum dibayarkan. Angkanya kembali nol setelah admin menutup periode, lalu naik lagi seiring Anda mengajar.',
                        ],
                    ],
                    [
                        'title' => 'Riwayat Struk',
                        'items' => [
                            'Setiap struk menyimpan tarif yang berlaku saat itu, jadi kenaikan tarif tidak mengubah struk lama.',
                            'Struk yang ditandai dibatalkan berarti diterbitkan ulang oleh admin, dan kehadirannya dihitung kembali pada periode berikutnya.',
                            'Kalau ada angka yang menurut Anda tidak cocok, sampaikan ke admin beserta nomor struknya.',
                        ],
                    ],
                ],
            ],

            'admin.result.index' => [
                'title' => 'Panduan Result',
                'summary' => 'Tempat menentukan apa saja yang dinilai dari setiap anak, dan melihat hasilnya. Aspek yang Anda isi di sini adalah yang muncul di layar guru saat menilai di menu Absen.',
                'sections' => [
                    [
                        'title' => 'Menentukan Aspek Penilaian',
                        'items' => [
                            'Satu aspek terdiri dari nama dan indikator. Nama adalah yang dinilai, indikator menjelaskan maksudnya supaya guru tidak menebak.',
                            'Setiap pertemuan, guru wajib memberi skor 1 sampai 5 pada semua aspek yang aktif untuk setiap anak yang hadir.',
                            'Aspek berlaku untuk semua kelas, jadi tulislah yang bisa dipakai lintas mata pelajaran.',
                            'Urutan aspek bisa digeser naik turun; urutan itulah yang dilihat guru saat menilai.',
                        ],
                    ],
                    [
                        'title' => 'Mengubah dan Menghapus Aspek',
                        'items' => [
                            'Aspek yang sudah pernah dipakai menilai tidak bisa dihapus, supaya rapor lama tidak jadi bolong.',
                            'Kalau sebuah aspek tidak dipakai lagi, nonaktifkan saja. Aspek itu hilang dari layar guru tetapi nilai lamanya tetap muncul di rapor.',
                            'Mengubah nama atau indikator aspek berlaku surut ke seluruh rapor, karena yang tersimpan adalah kaitannya, bukan salinan namanya.',
                            'Selama belum ada satu pun aspek aktif, guru tidak akan bisa menyelesaikan penilaian di menu Absen.',
                        ],
                    ],
                    [
                        'title' => 'Membaca Hasil Anak',
                        'items' => [
                            'Angka besar di kartu adalah rata-rata semua aspek dari semua pertemuan yang anak itu hadiri.',
                            'Klik kartu anak untuk melihat rinciannya: rata-rata tiap aspek, nilai terendah dan tertinggi, serta tren naik atau turun.',
                            'Tren baru muncul setelah ada minimal 4 pertemuan yang dinilai, karena di bawah itu belum bisa disebut kecenderungan.',
                            'Tombol Download Rapor PDF di dalam rincian menghasilkan laporan siap cetak untuk orang tua.',
                        ],
                    ],
                ],
            ],

            'profile.edit' => [
                'title' => 'Panduan Profil',
                'summary' => 'Mengubah nama, email, dan password akun yang Anda pakai untuk masuk.',
                'sections' => [
                    [
                        'title' => 'Yang Bisa Diubah',
                        'items' => [
                            'Email di sini adalah email yang dipakai untuk login. Mengubahnya berarti mengubah cara Anda masuk.',
                            'Ganti password secara berkala, terutama kalau akun ini baru saja dibuatkan oleh admin.',
                            'Menghapus akun tidak menghapus data guru maupun jadwal mengajar, tetapi aksesnya hilang permanen.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, summary: string, sections: array<int, array{title: string, items: array<int, string>}>}
     */
    private static function bawaan(): array
    {
        return [
            'title' => 'Pusat Bantuan',
            'summary' => 'Panduan singkat untuk halaman yang sedang dibuka. Setiap menu punya panduannya sendiri, jadi buka tombol ini lagi setelah berpindah menu.',
            'sections' => [
                [
                    'title' => 'Cara Memakai',
                    'items' => [
                        'Periksa dulu filter atau pencarian yang sedang aktif sebelum menjalankan aksi massal.',
                        'Buka detail atau edit untuk memastikan data yang akan diproses sudah benar.',
                        'Menambah dan mengubah data pokok seperti guru, ruang, sesi, paket, dan siswa dilakukan di menu Workshop.',
                    ],
                ],
            ],
        ];
    }
}
