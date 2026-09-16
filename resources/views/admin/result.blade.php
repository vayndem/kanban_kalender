<x-admin-layout activeTab="result">
    <div x-data="resultHandler({
        initialAspek: @js($aspekList),
        initialSiswa: @js($siswaList),
        routes: {
            aspekStore: @js(route('admin.result.simpanAspek')),
            aspekBase: @js(url('admin/result/aspek')),
            aspekUrutan: @js(route('admin.result.urutkanAspek')),
            raporBase: @js(url('admin/result/siswa')),
        },
    })">

        <div x-show="isLoading" x-cloak
            class="fixed inset-0 z-[200] flex cursor-wait items-center justify-center bg-black/40 backdrop-blur-xs">
            <div class="app-card flex items-center gap-3 px-6 py-5">
                <i class="fas fa-circle-notch fa-spin text-xl text-primary"></i>
                <p class="text-sm font-bold text-base-content">Sedang diproses...</p>
            </div>
        </div>

        {{-- Aspek penilaian --}}
        <div class="app-card app-card-pad mb-6">
            <div class="mb-5 flex flex-col gap-4 border-b border-base-300 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="brand-chip h-11 w-11 text-lg"><i class="fas fa-medal"></i></span>
                    <div>
                        <h3 class="text-xl font-black tracking-tight text-base-content sm:text-2xl">Aspek Penilaian</h3>
                        <p class="mt-0.5 text-xs text-base-content/70 sm:text-sm">
                            Setiap pertemuan, guru menilai anak pada semua aspek di bawah ini dengan skor 1 sampai 5.
                        </p>
                    </div>
                </div>

                <button type="button" @click="bukaForm()" class="btn btn-primary btn-sm w-full shrink-0 sm:w-auto">
                    <i class="fas fa-plus"></i> Tambah Aspek
                </button>
            </div>

            <div x-show="! siapDinilai" x-cloak
                class="mb-4 flex items-start gap-3 rounded-box border border-warning/40 bg-warning/10 p-3">
                <i class="fas fa-triangle-exclamation mt-0.5 text-warning"></i>
                <p class="text-sm text-base-content">
                    Belum ada aspek aktif. Selama ini kosong, <span class="font-bold">guru tidak bisa menyelesaikan
                    penilaian</span> di menu Absen.
                </p>
            </div>

            {{-- Form aspek --}}
            <div x-show="formTerbuka" x-cloak x-transition
                class="mb-5 rounded-box border border-primary/40 bg-primary/5 p-4">
                <p class="mb-3 text-xs font-black uppercase tracking-wider text-primary"
                    x-text="form.id ? 'Ubah Aspek' : 'Aspek Baru'"></p>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div>
                        <label class="app-label">Nama Aspek</label>
                        <input type="text" x-model="form.nama" maxlength="120" class="app-input"
                            placeholder="Contoh: Vocabulary Mastery">
                    </div>
                    <div>
                        <label class="app-label">Indikator</label>
                        <input type="text" x-model="form.indikator" maxlength="255" class="app-input"
                            placeholder="Contoh: Mengenal dan menggunakan vocabulary sesuai materi level">
                    </div>
                </div>

                <label class="mt-3 flex min-h-11 w-fit cursor-pointer items-center gap-2 text-sm font-bold text-base-content">
                    <input type="checkbox" x-model="form.aktif" class="checkbox checkbox-primary">
                    Dipakai saat menilai
                </label>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <button type="button" @click="simpanAspek()" :disabled="isLoading"
                        class="btn btn-primary btn-sm w-full sm:w-auto">
                        <i class="fas fa-floppy-disk"></i> Simpan
                    </button>
                    <button type="button" @click="tutupForm()" class="btn btn-neutral btn-sm w-full sm:w-auto">Batal</button>
                </div>
            </div>

            <div x-show="aspekList.length === 0" x-cloak class="app-empty border-0">
                <div class="app-empty-icon"><i class="fas fa-list-check"></i></div>
                <p class="app-empty-title">Belum ada aspek penilaian.</p>
                <p class="app-empty-text">Tekan Tambah Aspek untuk menentukan apa saja yang dinilai dari setiap anak.</p>
            </div>

            <div class="space-y-2">
                <template x-for="(aspek, i) in [...aspekList].sort((a, b) => a.urutan - b.urutan || a.id - b.id)"
                    :key="aspek.id">
                    <div class="flex flex-col gap-3 rounded-box border p-3 transition sm:flex-row sm:items-center"
                        :class="aspek.aktif ? 'border-base-300 bg-base-100' : 'border-dashed border-base-300 bg-base-200/60 opacity-70'">

                        <div class="flex items-start gap-3 sm:flex-1">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-selector bg-primary/10 text-xs font-black text-primary"
                                x-text="i + 1"></span>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 font-black text-base-content">
                                    <span x-text="aspek.nama"></span>
                                    <span x-show="! aspek.aktif" class="badge badge-sm font-bold">Nonaktif</span>
                                </p>
                                <p class="mt-0.5 text-xs leading-snug text-base-content/70" x-text="aspek.indikator"></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 sm:shrink-0">
                            <button type="button" @click="geser(aspek, -1)" :disabled="i === 0"
                                class="icon-action" :class="i === 0 ? 'opacity-30' : ''" title="Naikkan urutan">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" @click="geser(aspek, 1)" :disabled="i === aspekList.length - 1"
                                class="icon-action" :class="i === aspekList.length - 1 ? 'opacity-30' : ''"
                                title="Turunkan urutan">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" @click="bukaForm(aspek)" class="icon-action-primary" title="Ubah">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" @click="alihkanAktif(aspek)"
                                :class="aspek.aktif ? 'icon-action-warning' : 'icon-action-success'"
                                :title="aspek.aktif ? 'Nonaktifkan' : 'Aktifkan'">
                                <i class="fas" :class="aspek.aktif ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                            <button type="button" @click="hapusAspek(aspek)" class="icon-action-danger" title="Hapus">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Hasil anak --}}
        <div class="app-card app-card-pad">
            <div class="mb-5 flex flex-col gap-4 border-b border-base-300 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-xl font-black tracking-tight text-base-content sm:text-2xl">Hasil Anak</h3>
                    <p class="mt-0.5 text-xs text-base-content/70 sm:text-sm">
                        Rekap perkembangan setiap anak dari pertemuan yang sudah dinilai guru.
                    </p>
                </div>
            </div>

            <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="app-stat">
                    <div class="app-stat-value" x-text="statSiswa.total"></div>
                    <div class="app-stat-label">Total Anak</div>
                </div>
                <div class="app-stat">
                    <div class="app-stat-value text-success" x-text="statSiswa.dinilai"></div>
                    <div class="app-stat-label">Sudah Dinilai</div>
                </div>
                <div class="app-stat">
                    <div class="app-stat-value text-warning" x-text="statSiswa.belum"></div>
                    <div class="app-stat-label">Belum Ada Nilai</div>
                </div>
                <div class="app-stat">
                    <div class="app-stat-value text-primary" x-text="statSiswa.rata ?? '-'"></div>
                    <div class="app-stat-label">Rata-rata Semua</div>
                </div>
            </div>

            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative w-full lg:max-w-xs">
                    <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/60"></i>
                    <input type="search" x-model="cariSiswa" placeholder="Cari nama atau kelas..."
                        class="app-input pl-10">
                </label>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="flex items-center gap-2">
                        <span class="shrink-0 text-xs font-bold uppercase tracking-wider text-base-content/60">Urutkan</span>
                        <select x-model="urutSiswa" class="select select-sm" data-native-select="true">
                            <option value="nama">Nama</option>
                            <option value="nilai">Nilai tertinggi</option>
                            <option value="pertemuan">Pertemuan terbanyak</option>
                        </select>
                    </label>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm font-bold text-base-content/80">
                        <input type="checkbox" x-model="hanyaSudahDinilai" class="checkbox checkbox-primary">
                        Hanya yang sudah dinilai
                    </label>
                </div>

                <span class="badge badge-primary badge-sm font-bold lg:ml-auto"
                    x-text="daftarSiswa.length + ' anak'"></span>
            </div>

            <div x-show="daftarSiswa.length === 0" x-cloak class="app-empty border-0">
                <div class="app-empty-icon"><i class="fas fa-user-slash"></i></div>
                <p class="app-empty-title">Tidak ada anak yang cocok.</p>
                <p class="app-empty-text">Coba ubah kata pencarian atau matikan saringan di atas.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="siswa in daftarSiswa" :key="siswa.id">
                    <button type="button" @click="bukaRapor(siswa)"
                        class="app-card-hover overflow-hidden text-left transition">
                        <div class="flex items-start gap-3 border-b border-base-300 bg-base-200/60 px-4 py-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent text-sm font-black text-white">
                                <span x-text="siswa.nama.charAt(0)"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-black text-base-content" x-text="siswa.nama"></p>
                                <p class="truncate text-xs text-base-content/70">
                                    <span x-text="siswa.kelas || 'Kelas belum diisi'"></span>
                                    <template x-if="siswa.kemampuan">
                                        <span> · <span x-text="siswa.kemampuan"></span></span>
                                    </template>
                                </p>
                            </div>
                            <span class="shrink-0 text-right">
                                <span class="block text-lg font-black leading-none"
                                    :class="warnaNilai(siswa.rata_nilai)"
                                    x-text="siswa.rata_nilai ?? '-'"></span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/60">Rata</span>
                            </span>
                        </div>

                        <div class="grid grid-cols-3 divide-x divide-base-300 px-2 py-3 text-center">
                            <div>
                                <p class="text-base font-black text-base-content" x-text="siswa.total_pertemuan"></p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pertemuan</p>
                            </div>
                            <div>
                                <p class="text-base font-black text-base-content" x-text="siswa.hadir"></p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-base-content/60">Hadir</p>
                            </div>
                            <div>
                                <p class="text-base font-black text-base-content"
                                    x-text="siswa.persen_kehadiran + '%'"></p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kehadiran</p>
                            </div>
                        </div>

                        <div class="border-t border-base-300 px-4 py-2 text-[11px] text-base-content/60">
                            <template x-if="siswa.terakhir_dinilai">
                                <span>Terakhir dinilai <span class="font-bold" x-text="siswa.terakhir_dinilai"></span></span>
                            </template>
                            <template x-if="! siswa.terakhir_dinilai">
                                <span class="text-warning">Belum pernah dinilai</span>
                            </template>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Rapor per anak --}}
        <template x-if="raporUntuk">
            <div x-transition.opacity
                class="fixed inset-0 z-[120] flex items-end justify-center bg-black/70 p-0 backdrop-blur-xs sm:items-center sm:p-4"
                @click="tutupRapor()">
                <div @click.stop
                    class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-t-3xl border border-base-300 bg-base-100 shadow-2xl sm:rounded-3xl">

                    <div class="sticky top-0 z-10 flex items-start justify-between gap-3 bg-gradient-to-r from-primary to-accent px-5 py-4 text-white">
                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-white/75">Rapor Perkembangan</p>
                            <h3 class="mt-1 truncate text-lg font-black" x-text="raporUntuk.nama"></h3>
                        </div>
                        <button type="button" @click="tutupRapor()"
                            class="shrink-0 rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">
                            Tutup
                        </button>
                    </div>

                    <div class="space-y-5 p-5">
                        <div x-show="isLoadingRapor" class="flex items-center justify-center gap-3 py-10">
                            <i class="fas fa-circle-notch fa-spin text-xl text-primary"></i>
                            <span class="text-sm font-bold text-base-content/70">Memuat rapor...</span>
                        </div>

                        <template x-if="! isLoadingRapor && raporSiswa && raporSiswa.ringkasan.total_pertemuan === 0">
                            <div class="app-empty border-0">
                                <div class="app-empty-icon"><i class="fas fa-clipboard-question"></i></div>
                                <p class="app-empty-title">Belum ada pertemuan yang dinilai.</p>
                                <p class="app-empty-text">Rapor akan terisi setelah guru menyelesaikan penilaian di menu Absen.</p>
                            </div>
                        </template>

                        <template x-if="! isLoadingRapor && raporSiswa && raporSiswa.ringkasan.total_pertemuan > 0">
                            <div class="space-y-5">
                                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                    <div class="app-stat">
                                        <div class="app-stat-value" x-text="raporSiswa.ringkasan.total_pertemuan"></div>
                                        <div class="app-stat-label">Pertemuan</div>
                                    </div>
                                    <div class="app-stat">
                                        <div class="app-stat-value text-success"
                                            x-text="raporSiswa.ringkasan.persen_kehadiran + '%'"></div>
                                        <div class="app-stat-label">Kehadiran</div>
                                    </div>
                                    <div class="app-stat">
                                        <div class="app-stat-value" :class="warnaNilai(raporSiswa.ringkasan.rata_nilai)"
                                            x-text="raporSiswa.ringkasan.rata_nilai ?? '-'"></div>
                                        <div class="app-stat-label">Rata-rata Nilai</div>
                                    </div>
                                    <div class="app-stat">
                                        <div class="app-stat-value" :class="warnaTren(raporSiswa.ringkasan.tren)">
                                            <i class="fas text-base" :class="ikonTren(raporSiswa.ringkasan.tren)"></i>
                                            <span class="ml-1" x-text="labelTren(raporSiswa.ringkasan.tren)"></span>
                                        </div>
                                        <div class="app-stat-label">Tren Nilai</div>
                                    </div>
                                </div>

                                <template x-if="raporSiswa.per_aspek.length > 0">
                                    <div>
                                        <p class="mb-2 text-xs font-black uppercase tracking-wider text-base-content/60">
                                            Rincian Per Aspek
                                        </p>
                                        <div class="space-y-2">
                                            <template x-for="aspek in raporSiswa.per_aspek" :key="aspek.aspek_id">
                                                <div class="rounded-box border border-base-300 p-3">
                                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                                        <div class="min-w-0 flex-1">
                                                            <p class="font-black text-base-content" x-text="aspek.nama"></p>
                                                            <p class="mt-0.5 text-xs leading-snug text-base-content/60"
                                                                x-text="aspek.indikator"></p>
                                                        </div>
                                                        <div class="shrink-0 text-right">
                                                            <span class="text-lg font-black" :class="warnaNilai(aspek.rata)"
                                                                x-text="aspek.rata"></span>
                                                            <span class="block text-[10px] text-base-content/60">
                                                                dari <span x-text="aspek.jumlah_dinilai"></span> penilaian
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-base-200">
                                                        <div class="h-full rounded-full transition-all"
                                                            :class="aspek.rata >= 4 ? 'bg-success' : (aspek.rata >= 3 ? 'bg-primary' : (aspek.rata >= 2 ? 'bg-warning' : 'bg-error'))"
                                                            :style="`width: ${(aspek.rata / 5) * 100}%`"></div>
                                                    </div>

                                                    <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-base-content/60">
                                                        <span>Terendah <span class="font-bold" x-text="aspek.terendah"></span></span>
                                                        <span>Tertinggi <span class="font-bold" x-text="aspek.tertinggi"></span></span>
                                                        <span x-show="aspek.tren" :class="warnaTren(aspek.tren)">
                                                            <i class="fas" :class="ikonTren(aspek.tren)"></i>
                                                            <span class="font-bold" x-text="labelTren(aspek.tren)"></span>
                                                        </span>
                                                    </p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div>
                                    <p class="mb-2 text-xs font-black uppercase tracking-wider text-base-content/60">
                                        Per Mata Pelajaran
                                    </p>
                                    <div class="space-y-2">
                                        <template x-for="m in raporSiswa.per_mapel" :key="m.mapel">
                                            <div class="rounded-box border border-base-300 p-3">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <p class="font-black text-base-content" x-text="m.mapel"></p>
                                                        <p class="text-xs text-base-content/60" x-text="m.guru"></p>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-xs">
                                                        <span class="text-base-content/70">
                                                            <span class="font-bold" x-text="m.hadir"></span> /
                                                            <span x-text="m.jumlah_pertemuan"></span> hadir
                                                        </span>
                                                        <span class="badge badge-primary badge-sm font-bold"
                                                            x-text="m.rata_nilai ?? '-'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="rounded-box border border-base-300 bg-base-200/50 p-3">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-base-content">
                                                <i class="fas fa-print text-primary"></i> Cetak Dokumen
                                            </p>
                                            <p class="mt-0.5 text-xs text-base-content/70">
                                                Pilih dulu pertemuan mana yang mau dijadikan sertifikat atau rapor.
                                            </p>
                                        </div>
                                        <button type="button" x-show="! modeCetak" @click="bukaModeCetak()"
                                            class="btn btn-export btn-sm w-full shrink-0 sm:w-auto">
                                            <i class="fas fa-file-arrow-down"></i> Siapkan Cetak
                                        </button>
                                    </div>

                                    <div x-show="modeCetak" x-cloak x-transition class="mt-4 space-y-4">
                                        <div>
                                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-xs font-black uppercase tracking-wider text-base-content/60">
                                                    Pertemuan
                                                    <span class="text-primary" x-text="pertemuanDipilih.length"></span>
                                                    dari <span x-text="daftarPertemuan.length"></span> dipilih
                                                </p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <button type="button" @click="pilihBulanIni()"
                                                        class="btn btn-ghost btn-xs">Bulan ini</button>
                                                    <button type="button" @click="alihSemuaPertemuan()"
                                                        class="btn btn-ghost btn-xs"
                                                        x-text="semuaPertemuanDipilih ? 'Kosongkan' : 'Pilih semua'"></button>
                                                </div>
                                            </div>

                                            <div class="max-h-56 space-y-1.5 overflow-y-auto rounded-field bg-base-100 p-2">
                                                <template x-for="p in daftarPertemuan" :key="p.detail_id">
                                                    <label class="flex cursor-pointer items-start gap-3 rounded-field px-2 py-2 transition hover:bg-primary/10">
                                                        <input type="checkbox" :value="String(p.detail_id)"
                                                            x-model="pertemuanDipilih"
                                                            class="checkbox checkbox-primary mt-0.5 shrink-0 sm:checkbox-sm">
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block truncate text-sm font-bold text-base-content"
                                                                x-text="p.materi"></span>
                                                            <span class="block text-[11px] text-base-content/60">
                                                                <span x-text="p.tanggal"></span> ·
                                                                <span x-text="p.diajar_oleh"></span>
                                                            </span>
                                                        </span>
                                                        <span class="shrink-0 text-right">
                                                            <template x-if="p.hadir">
                                                                <span class="text-sm font-black"
                                                                    :class="warnaNilai(p.nilai)"
                                                                    x-text="p.persen + '%'"></span>
                                                            </template>
                                                            <template x-if="! p.hadir">
                                                                <span class="badge badge-sm font-bold">Absen</span>
                                                            </template>
                                                        </span>
                                                    </label>
                                                </template>

                                                <p x-show="daftarPertemuan.length === 0"
                                                    class="px-2 py-4 text-center text-xs text-base-content/60">
                                                    Belum ada pertemuan yang bisa dicetak.
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="mb-2 text-xs font-black uppercase tracking-wider text-base-content/60">
                                                Catatan Rapor <span class="font-normal normal-case text-base-content/50">(opsional)</span>
                                            </p>
                                            <div class="grid grid-cols-1 gap-2 lg:grid-cols-2">
                                                <label class="block">
                                                    <span class="app-label">Student Strength</span>
                                                    <textarea x-model="formCetak.kekuatan" rows="2" maxlength="2000"
                                                        class="app-input" placeholder="Kelebihan anak bulan ini..."></textarea>
                                                </label>
                                                <label class="block">
                                                    <span class="app-label">Area to Improve</span>
                                                    <textarea x-model="formCetak.perbaikan" rows="2" maxlength="2000"
                                                        class="app-input" placeholder="Yang masih perlu dilatih..."></textarea>
                                                </label>
                                                <label class="block">
                                                    <span class="app-label">Teacher's Comment</span>
                                                    <textarea x-model="formCetak.komentar" rows="2" maxlength="2000"
                                                        class="app-input" placeholder="Pesan guru untuk orang tua..."></textarea>
                                                </label>
                                                <label class="block">
                                                    <span class="app-label">Plan for Next Month</span>
                                                    <textarea x-model="formCetak.rencana" rows="2" maxlength="2000"
                                                        class="app-input" placeholder="Rencana kegiatan bulan depan..."></textarea>
                                                </label>
                                            </div>
                                            <p class="mt-1.5 text-[11px] text-base-content/60">
                                                Dibiarkan kosong akan tercetak sebagai garis titik-titik untuk diisi tangan.
                                                Isian ini tidak disimpan, hanya ikut pada cetakan kali ini.
                                            </p>
                                        </div>

                                        <label class="block">
                                            <span class="app-label">Judul Sertifikat</span>
                                            <input type="text" x-model="formCetak.judul_sertifikat" maxlength="120"
                                                class="app-input" placeholder="Certificate of Achievement">
                                        </label>

                                        <form method="POST" x-ref="formCetak" target="_blank" class="contents">
                                            @csrf
                                            <template x-for="id in pertemuanDipilih" :key="id">
                                                <input type="hidden" name="pertemuan[]" :value="id">
                                            </template>
                                            <input type="hidden" name="judul_sertifikat" :value="formCetak.judul_sertifikat">
                                            <input type="hidden" name="kekuatan" :value="formCetak.kekuatan">
                                            <input type="hidden" name="perbaikan" :value="formCetak.perbaikan">
                                            <input type="hidden" name="komentar" :value="formCetak.komentar">
                                            <input type="hidden" name="rencana" :value="formCetak.rencana">
                                        </form>

                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <button type="button" @click="cetak('rapor')"
                                                :disabled="pertemuanDipilih.length === 0"
                                                :class="pertemuanDipilih.length === 0 ? 'opacity-50' : ''"
                                                class="btn btn-export btn-sm flex-1">
                                                <i class="fas fa-file-lines"></i> Cetak Rapor
                                            </button>
                                            <button type="button" @click="cetak('sertifikat')"
                                                :disabled="pertemuanDipilih.length === 0"
                                                :class="pertemuanDipilih.length === 0 ? 'opacity-50' : ''"
                                                class="btn btn-accent btn-sm flex-1">
                                                <i class="fas fa-award"></i> Cetak Sertifikat
                                            </button>
                                        </div>

                                        <p x-show="pertemuanDipilih.length === 0"
                                            class="text-center text-xs text-warning">
                                            Centang minimal satu pertemuan dulu.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
