@php
    $routeName = request()->route()?->getName();
    $activeTab = request()->string('tab')->toString();

    $helpContent = [
        'title' => 'Panduan Admin',
        'summary' => 'Halaman ini dipakai untuk mengelola operasional harian. Gunakan bantuan ini sebagai ringkasan fungsi, alur kerja, dan hal yang perlu diperhatikan.',
        'sections' => [
            [
                'title' => 'Fungsi Utama',
                'items' => [
                    'Lihat isi halaman ini dan gunakan tombol aksi utama di bagian atas.',
                    'Periksa filter atau pencarian sebelum mengeksekusi aksi massal.',
                    'Gunakan detail atau edit untuk memastikan data yang diproses sudah benar.',
                ],
            ],
        ],
    ];

    if ($routeName === 'dashboard' && $activeTab === 'jadwal') {
        $helpContent = [
            'title' => 'Panduan Jadwal',
            'summary' => 'Tab ini dipakai untuk menyusun, memindahkan, mengedit, dan mengekspor jadwal belajar. Sistem sudah membantu memfilter bentrok guru, ruang, dan siswa pada slot yang sama.',
            'sections' => [
                [
                    'title' => 'Fungsi Halaman',
                    'items' => [
                        'Mata pelajaran, guru, ruang, dan sesi sekarang dikelola dari menu Workshop, bukan di tab ini.',
                        'Klik kartu jadwal untuk edit kelas, siswa, guru, ruang, dan catatan terkait.',
                        'Gunakan Export / Copy untuk PDF jadwal atau copy teks WhatsApp.',
                    ],
                ],
                [
                    'title' => 'Tips Pakai',
                    'items' => [
                        'Pencarian universal akan menyaring hari, sesi, mapel, guru, ruang, dan siswa sekaligus.',
                        'Kalau ingin memindahkan kelas, cek dulu slot tujuan agar isi kartu tetap sesuai kapasitas dan kebutuhan.',
                        'Gunakan fitur stash sebelum perubahan besar agar jadwal bisa disimpan sebagai cadangan.',
                    ],
                ],
            ],
        ];
    } elseif ($routeName === 'dashboard' && $activeTab === 'data_siswa') {
        $helpContent = [
            'title' => 'Panduan Data Siswa',
            'summary' => 'Tab ini dipakai untuk mengelola data master siswa, arsip, paket, jadwal yang diikuti, dan ekspor daftar siswa. Gunakan filter untuk mempersempit data sebelum ekspor atau aksi massal.',
            'sections' => [
                [
                    'title' => 'Fungsi Halaman',
                    'items' => [
                        'Tambah dan edit siswa sekarang dilakukan dari menu Workshop, bukan di tab ini.',
                        'Arsipkan, pulihkan, atau hapus permanen data siswa tetap dari tab ini.',
                        'Filter siswa berdasarkan kelas, paket, sesi, guru, dan ruang.',
                        'Ekspor PDF akan mengikuti filter aktif yang sedang dipilih.',
                    ],
                ],
                [
                    'title' => 'Tips Pakai',
                    'items' => [
                        'Klik ikon info pada baris siswa untuk melihat jadwal yang diikuti dan menambah catatan.',
                        'Pastikan nomor WhatsApp siswa memakai format +62 agar fitur tagih dan reminder konsisten.',
                        'Kalau memakai aksi pilih banyak data, periksa dulu hasil filter agar yang terarsip benar-benar sesuai.',
                    ],
                ],
            ],
        ];
    } elseif ($routeName === 'admin.workshop.index') {
        $helpContent = [
            'title' => 'Panduan Workshop',
            'summary' => 'Tempat menambah dan mengubah data pokok: mata pelajaran, guru, ruang, sesi waktu, dan siswa. Setiap form menampilkan data yang sudah ada terkait apa yang sedang diketik, supaya tidak perlu bolak-balik cek halaman lain.',
            'sections' => [
                [
                    'title' => 'Fungsi Halaman',
                    'items' => [
                        'Kelola mata pelajaran, guru, ruang, sesi waktu, dan siswa dalam satu halaman.',
                        'Mengetik no HP siswa akan memeriksa apakah nomor itu sudah dipakai siswa lain (kemungkinan saudara).',
                        'Mengetik kelas akan menampilkan kelas yang sudah ada untuk grade tersebut.',
                    ],
                ],
                [
                    'title' => 'Tips Pakai',
                    'items' => [
                        'Peringatan kemiripan data hanya informasi, tidak menghalangi penyimpanan.',
                        'Menjadwalkan siswa ke kelas tetap dilakukan di tab Jadwal Pelajaran seperti biasa.',
                    ],
                ],
            ],
        ];
    } elseif ($routeName === 'admin.akunGuru.index') {
        $helpContent = [
            'title' => 'Panduan Akun Guru',
            'summary' => 'Kelola email dan akun login guru di sini. Untuk menambah atau mengubah data guru itu sendiri, gunakan menu Workshop.',
            'sections' => [
                [
                    'title' => 'Fungsi Halaman',
                    'items' => [
                        'Isi email guru sebelum akun login bisa dibuatkan.',
                        'Guru tanpa akun tetap bisa dijadwalkan seperti biasa.',
                    ],
                ],
            ],
        ];
    } elseif ($routeName === 'dashboard' && $activeTab === 'pembayaran') {
        $helpContent = [
            'title' => 'Panduan Pembayaran',
            'summary' => 'Tab ini dipakai untuk membuat tagihan, mencatat penerimaan pembayaran, mengelola diskon, menandai lunas, dan mencetak bukti pelunasan. Versi minimum formal ini memperketat tampilan dan validasi tanpa mengubah struktur database lama.',
            'sections' => [
                [
                    'title' => 'Fungsi Halaman',
                    'items' => [
                        'Tagihan membuat invoice baru untuk siswa yang dipilih.',
                        'Penagihan Massal membuat tagihan bulanan berdasarkan paket siswa dan sekarang menghindari duplikasi periode yang sama.',
                        'Catat Bayar, Ubah ke lunas, dan Selesaikan Seluruh Status akan memperbarui status serta detail pelunasan.',
                    ],
                ],
                [
                    'title' => 'Hal Penting',
                    'items' => [
                        'Cetak bukti akan mengikuti invoice yang tampil pada ringkasan yang sedang diklik, termasuk filter yang aktif.',
                        'Detail pembayaran akan menampilkan nominal pelunasan manual maupun pelunasan otomatis sistem.',
                        'Diskon spesifik keluarga dan diskon universal sama-sama diperhitungkan pada ringkasan dan struk.',
                    ],
                ],
                [
                    'title' => 'Tips Pakai',
                    'items' => [
                        'Gunakan filter bulan dan pencarian sebelum mencetak struk atau export PDF agar hasilnya sesuai kebutuhan.',
                        'Untuk pembayaran bertahap, isi nominal sesuai dana masuk dan tambahkan keterangan agar jejak administrasi jelas.',
                        'Gunakan Kelola Paket dan Kelola Diskon dengan hati-hati karena keduanya memengaruhi penagihan berikutnya.',
                    ],
                ],
            ],
        ];
    } elseif ($routeName === 'profile.edit') {
        $helpContent = [
            'title' => 'Panduan Profil',
            'summary' => 'Halaman ini dipakai untuk memperbarui profil akun admin, alamat email, dan password masuk.',
            'sections' => [
                [
                    'title' => 'Fungsi Halaman',
                    'items' => [
                        'Perbarui nama dan email akun yang dipakai untuk login.',
                        'Ganti password jika diperlukan untuk keamanan akun.',
                        'Hapus akun hanya jika memang sudah tidak akan dipakai lagi.',
                    ],
                ],
            ],
        ];
    }
@endphp

<div
    x-data="{ open: false, content: @js($helpContent) }"
    class="fixed bottom-5 right-5 z-[120]"
>
    <button
        type="button"
        @click="open = true"
        class="group flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-primary to-accent text-white shadow-xl shadow-success/20 transition hover:scale-105 hover:shadow-2xl hover:shadow-success/20 focus:outline-hidden focus:ring-4 focus:ring-primary dark:focus:ring-primary"
        aria-label="Buka panduan admin"
    >
        <span class="text-2xl font-black">?</span>
    </button>

    <template x-if="open">
    <div
        x-transition.opacity
        class="fixed inset-0 z-[121] flex items-end justify-end bg-base-200/45 p-4 backdrop-blur-xs sm:items-center sm:justify-center"
    >
        <div @click="open = false" class="absolute inset-0"></div>

        <div
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-xl overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-2xl"
        >
            <div class="bg-gradient-to-r from-primary to-accent px-6 py-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-white/75">Pusat Bantuan</p>
                        <h3 class="mt-2 text-xl font-black" x-text="content.title"></h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/85" x-text="content.summary"></p>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="rounded-full bg-white/15 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/25"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <div class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-6">
                <template x-for="section in content.sections" :key="section.title">
                    <section class="rounded-2xl border border-base-300 bg-base-200 p-4">
                        <h4 class="text-sm font-black uppercase tracking-[0.18em] text-success" x-text="section.title"></h4>
                        <ul class="mt-3 space-y-2 text-sm text-base-content/80">
                            <template x-for="item in section.items" :key="item">
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 h-2 w-2 flex-none rounded-full bg-success"></span>
                                    <span x-text="item"></span>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
            </div>
        </div>
    </div>
    </template>
</div>
