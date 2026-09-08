<div class="bg-base-100 p-4 md:p-6 rounded-xl shadow-lg border border-base-300 transition-all duration-200"
    x-data="pembayaranHandler({
        initialSummaries: @js($pembayaranSummaries),
        initialSiswas: @js($allSiswas),
        initialPakets: @js($pakets),
        initialDiskons: @js($diskons),
        initialBatchStatus: @js($batchStatus),
        routes: {
            pembayaranStore: @js(route('admin.pembayaran.store')),
            penagihanMassal: @js(route('admin.pembayaran.penagihanMassal')),
            lunasSemua: @js(route('admin.pembayaran.lunasSemua')),
            export: @js(route('admin.pembayaran.export')),
            lunasSiswaBase: @js(url('admin/pembayaran/lunas-siswa')),
            bayarSiswaBase: @js(url('admin/pembayaran/bayar-siswa')),
            keLunasMassalBase: @js(url('admin/pembayaran/ke-lunas-massal')),
            diskonBase: @js(url('admin/diskon')),
            diskonStore: @js(route('admin.diskon.store')),
            paketBase: @js(url('admin/paket')),
            paketStore: @js(route('admin.paket.store')),
        },
    })">

    {{-- Penghalang klik selama permintaan berjalan. Dulu tombol tetap bisa
         diklik saat server lambat merespons, sehingga admin menekan dua kali
         dan pembayaran tercatat ganda. --}}
    <div x-show="isLoading" x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-[2px] cursor-wait">
        <div
            class="bg-base-100 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border">
            <i class="fas fa-circle-notch fa-spin text-success text-xl"></i>
            <div>
                <p class="text-sm font-bold text-base-content">Sedang diproses...</p>
                <p class="text-[11px] text-base-content/60">Mohon tunggu, jangan menutup atau menekan tombol
                    lagi.</p>
            </div>
        </div>
    </div>

    <div
        class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4 border-b border-base-300 pb-4">
        <div>
            <h3 class="text-lg md:text-xl font-bold text-base-content flex items-center gap-2">
                <i class="fas fa-wallet text-success animate-pulse"></i>
                Administrasi Pembayaran Siswa
            </h3>
            <p class="text-base-content/60 mt-0.5 text-xs md:text-sm">
                Ringkasan keuangan ditampilkan per nomor HP keluarga untuk memudahkan penagihan, pelunasan, dan
                pencetakan bukti pembayaran.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 w-full lg:w-auto">
            <button @click="exportExcel()" class="btn-export flex-1 lg:flex-none text-xs md:text-sm">
                <i class="fas fa-file-excel"></i> <span class="hidden sm:inline">Export</span> Excel
            </button>
            <button @click="openDiskonManagerModal()" class="btn btn-accent flex-1 lg:flex-none text-xs md:text-sm">
                <i class="fas fa-tags"></i> Kelola Diskon
            </button>
            <button @click="prosesPenagihanMassal()" :disabled="isLoading"
                class="disabled:opacity-50 disabled:cursor-not-allowed btn btn-warning flex-1 lg:flex-none text-xs md:text-sm">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Penagihan Massal</span>
            </button>
            <button @click="openPaketModal()" class="btn btn-accent flex-1 lg:flex-none text-xs md:text-sm">
                <i class="fas fa-box"></i> <span class="hidden sm:inline">Kelola</span> Paket
            </button>
            <button @click="openAddPembayaran()" class="btn btn-primary flex-1 lg:flex-none text-xs md:text-sm">
                <i class="fas fa-plus"></i> Tagihan
            </button>
            <button @click="lunaskanSemua()" :disabled="isLoading"
                class="disabled:opacity-50 disabled:cursor-not-allowed btn-sacred w-full lg:w-auto text-xs md:text-sm">
                <i class="fas fa-check-double"></i>
                <span>Selesaikan Seluruh Status</span>
            </button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        <div
            class="rounded-xl border border-success/40 bg-success/10 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-success">Keluarga
                Ditampilkan</p>
            <p class="mt-2 text-2xl font-black text-success"
                x-text="summaryStats.totalFamilies"></p>
            <p class="mt-1 text-[11px] text-success">Sesuai filter aktif saat ini</p>
        </div>
        <div class="rounded-xl border border-red-200/70 dark:border-red-900/50 bg-red-50/70 dark:bg-red-950/20 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-red-700 dark:text-red-300">Total Tagihan
                Bersih</p>
            <p class="mt-2 text-lg font-black text-red-800 dark:text-red-200"
                x-text="formatCurrency(summaryStats.totalNet)"></p>
            <p class="mt-1 text-[11px] text-red-700/80 dark:text-red-300/80">Akumulasi kewajiban setelah diskon</p>
        </div>
        <div class="rounded-xl border border-primary/40 bg-primary/10 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-primary">Dana Sudah
                Tercatat</p>
            <p class="mt-2 text-lg font-black text-primary"
                x-text="formatCurrency(summaryStats.totalPaid)"></p>
            <p class="mt-1 text-[11px] text-primary/80">Nominal pembayaran yang sudah masuk</p>
        </div>
        <div
            class="rounded-xl border border-amber-200/70 dark:border-amber-900/50 bg-amber-50/70 dark:bg-amber-950/20 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300">Sisa Piutang
                Aktif</p>
            <p class="mt-2 text-lg font-black text-amber-800 dark:text-amber-200"
                x-text="formatCurrency(summaryStats.totalRemaining)"></p>
            <p class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-300/80">Nilai yang belum terlunasi</p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="rounded-xl border px-4 py-3.5"
            :class="batchStatus?.penagihan_massal ?
                'border-success/40 bg-success/10' :
                'border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20'">
            <div class="flex items-start gap-2.5">
                <i class="fas mt-0.5 text-base"
                    :class="batchStatus?.penagihan_massal ? 'fa-circle-check text-success' :
                        'fa-circle-exclamation text-amber-500'"></i>
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wider"
                        :class="batchStatus?.penagihan_massal ? 'text-success' :
                            'text-amber-700 dark:text-amber-300'">
                        Penagihan Massal <span x-text="periodeLabel"></span>
                    </p>
                    <template x-if="batchStatus?.penagihan_massal">
                        <p
                            class="text-[11px] mt-1 font-semibold text-success leading-relaxed">
                            Sudah dijalankan
                            <span x-text="batchStatus.penagihan_massal.dijalankan_pada"></span>
                            <template x-if="batchStatus.penagihan_massal.oleh">
                                <span>oleh <span class="font-black"
                                        x-text="batchStatus.penagihan_massal.oleh"></span></span>
                            </template>
                            &mdash; <span x-text="batchStatus.penagihan_massal.jumlah_diproses"></span> tagihan dibuat.
                            <span class="block mt-1 text-success font-medium">Tidak bisa
                                dijalankan lagi bulan ini, supaya tidak ada tagihan ganda.</span>
                        </p>
                    </template>
                    <template x-if="!batchStatus?.penagihan_massal">
                        <p class="text-[11px] mt-1 font-semibold text-amber-800 dark:text-amber-200 leading-relaxed">
                            Belum dijalankan bulan ini. Tombol <span class="font-black">Penagihan Massal</span> akan
                            membuat tagihan
                            untuk semua siswa yang punya paket, dan hanya bisa sekali dalam sebulan.
                        </p>
                    </template>
                </div>
            </div>
        </div>

        <div class="rounded-xl border px-4 py-3.5"
            :class="batchStatus?.pelunasan_massal ?
                'border-success/40 bg-success/10' :
                'border-base-300 bg-base-200'">
            <div class="flex items-start gap-2.5">
                <i class="fas mt-0.5 text-base"
                    :class="batchStatus?.pelunasan_massal ? 'fa-circle-check text-success' :
                        'fa-shield-halved text-base-content/50'"></i>
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wider"
                        :class="batchStatus?.pelunasan_massal ? 'text-success' :
                            'text-base-content/70'">
                        Selesaikan Seluruh Status <span x-text="periodeLabel"></span>
                    </p>
                    <template x-if="batchStatus?.pelunasan_massal">
                        <p
                            class="text-[11px] mt-1 font-semibold text-success leading-relaxed">
                            Sudah dijalankan
                            <span x-text="batchStatus.pelunasan_massal.dijalankan_pada"></span>
                            <template x-if="batchStatus.pelunasan_massal.oleh">
                                <span>oleh <span class="font-black"
                                        x-text="batchStatus.pelunasan_massal.oleh"></span></span>
                            </template>
                            &mdash; <span x-text="batchStatus.pelunasan_massal.jumlah_diproses"></span> tagihan ditutup.
                            <span class="block mt-1 text-success font-medium">Terkunci
                                sampai bulan depan, supaya tagihan baru tidak ikut tersapu jadi lunas.</span>
                        </p>
                    </template>
                    <template x-if="!batchStatus?.pelunasan_massal">
                        <p class="text-[11px] mt-1 font-semibold text-base-content/70 leading-relaxed">
                            Belum dijalankan bulan ini. Tombol ini menutup <span class="font-black">seluruh</span>
                            tagihan aktif
                            menjadi lunas tanpa uang masuk, jadi pakai hanya saat tutup buku bulanan.
                        </p>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 bg-base-200 p-4 rounded-xl border border-base-300">
        <div class="w-full">
            <label
                class="block text-[10px] font-bold text-base-content/50 uppercase tracking-wider mb-1.5">Cari
                Nama / No HP / Keterangan</label>
            <div class="relative">
                <input type="text" x-model.debounce.200ms="filterSearch" placeholder="Ketik kata kunci..."
                    class="w-full rounded-lg border-base-300 text-sm focus:ring-2 focus:ring-primary focus:border-primary pl-9 transition-all">
                <i class="fas fa-search absolute left-3 top-3 text-base-content/50 text-xs"></i>
            </div>
        </div>
        <div class="w-full">
            <label
                class="block text-[10px] font-bold text-base-content/50 uppercase tracking-wider mb-1.5">Filter
                Bulan</label>
            <select x-model="filterBulan"
                class="w-full rounded-lg border-base-300 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                <option value="all">Semua Bulan</option>
                <option value="01">Januari</option>
                <option value="02">Februari</option>
                <option value="03">Maret</option>
                <option value="04">April</option>
                <option value="05">Mei</option>
                <option value="06">Juni</option>
                <option value="07">Juli</option>
                <option value="08">Agustus</option>
                <option value="09">September</option>
                <option value="10">Oktober</option>
                <option value="11">November</option>
                <option value="12">Desember</option>
            </select>
        </div>
        <div class="w-full sm:col-span-2 lg:col-span-1">
            <label
                class="block text-[10px] font-bold text-base-content/50 uppercase tracking-wider mb-2">Filter
                Status Kelayakan</label>
            <div
                class="flex flex-wrap items-center gap-2 bg-base-100 p-1.5 rounded-lg border">
                <label
                    class="flex-1 inline-flex items-center justify-center text-xs text-red-500 font-bold cursor-pointer px-2 py-1 rounded md:hover:bg-red-50 dark:hover:bg-red-950/20 transition-all">
                    <input type="radio" x-model="filterStatus" value="0"
                        class="text-red-500 focus:ring-0 w-3 h-3">
                    <span class="ml-1.5">Belum</span>
                </label>
                <label
                    class="flex-1 inline-flex items-center justify-center text-xs text-orange-500 font-bold cursor-pointer px-2 py-1 rounded md:hover:bg-orange-50 dark:hover:bg-orange-950/20 transition-all">
                    <input type="radio" x-model="filterStatus" value="1"
                        class="text-orange-500 focus:ring-0 w-3 h-3">
                    <span class="ml-1.5">Tertagih</span>
                </label>
                <label
                    class="flex-1 inline-flex items-center justify-center text-xs text-success font-bold cursor-pointer px-2 py-1 rounded md:hover:bg-success/20 transition-all">
                    <input type="radio" x-model="filterStatus" value="2"
                        class="text-success focus:ring-0 w-3 h-3">
                    <span class="ml-1.5">Lunas</span>
                </label>
            </div>
        </div>
    </div>

    <template x-if="isDesktop">
        <div class="overflow-x-auto border border-base-300 rounded-xl relative">
            <table class="min-w-full divide-y divide-base-300">
                <thead class="bg-base-200 text-left">
                    <tr>
                        <th
                            class="px-6 py-4 text-xs font-bold text-base-content/50 uppercase tracking-wider">
                            No
                            HP / Anggota Keluarga</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-base-content/50 uppercase tracking-wider">
                            Status & Posisi Pembayaran</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-base-content/50 uppercase tracking-wider">
                            Ringkasan Administratif</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-base-content/50 uppercase tracking-wider">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-base-100 divide-y divide-base-300">
                    <template x-for="item in displayedSummaries" :key="item.no_hp">
                        <tr class="hover:bg-base-200/70 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-base-content font-mono"
                                    x-text="item.no_hp"></span>
                                <span class="block text-[11px] text-primary font-semibold mt-0.5"
                                    x-text="item.siswa_names"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="px-2 py-1 rounded-md font-mono font-bold text-sm max-w-max"
                                        :class="{
                                            'bg-red-50 dark:bg-red-900/20 text-red-600': item.status == 0,
                                            'bg-orange-50 dark:bg-orange-900/20 text-orange-600': item.status == 1,
                                            'bg-success/10 text-success': item.status == 2
                                        }">
                                        <span x-text="item.status_label"></span> - Rp <span
                                            x-text="new Intl.NumberFormat('id-ID').format(item.total_akhir)"></span>
                                    </span>
                                    <template x-if="item.nominal_diskon > 0">
                                        <div class="flex items-center gap-1 mt-1 text-[10px] text-red-500 font-bold">
                                            <i class="fas fa-percent text-[9px]"></i>
                                            <span
                                                x-text="'Potongan aktif: ' + formatCurrency(item.nominal_diskon)"></span>
                                        </div>
                                    </template>
                                    <div class="mt-2 w-full max-w-[240px]">
                                        <div class="flex items-center justify-between text-[10px] font-black mb-1"
                                            :class="{
                                                'text-red-500': item.status == 0,
                                                'text-amber-600 dark:text-amber-400': item.status == 1,
                                                'text-success': item.status == 2
                                            }">
                                            <span><i class="fas fa-chart-simple mr-1"></i>Progres Bayar</span>
                                            <span x-text="item.paid_percent + '%'"></span>
                                        </div>
                                        <div
                                            class="w-full h-2.5 bg-base-300 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="{
                                                    'bg-red-400': item.status == 0,
                                                    'bg-amber-500': item.status == 1,
                                                    'bg-success': item.status == 2
                                                }"
                                                :style="'width: ' + item.paid_percent + '%'"></div>
                                        </div>
                                        <div class="flex items-center justify-between mt-1.5 gap-2">
                                            <span
                                                class="text-[10px] font-bold text-success truncate"
                                                x-text="'Sudah Masuk: ' + formatCurrency(item.total_sudah_dibayar)"></span>
                                            <span class="text-[10px] font-bold text-red-500 dark:text-red-400 truncate"
                                                x-text="'Sisa: ' + formatCurrency(item.remaining_amount)"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <p class="text-xs text-base-content/70 truncate font-semibold"
                                    x-text="item.gabungan_keterangan || 'Belum ada keterangan tagihan.'"></p>
                                <template x-if="item.status != 2">
                                    <div class="mt-1 space-y-0.5">
                                        <span class="text-[9px] text-base-content/50 font-medium block"
                                            x-text="'Periode input: ' + item.tanggal_format"></span>
                                        <span class="text-[9px] text-base-content/50 font-medium block"
                                            x-text="'Status administrasi: ' + item.status_label"></span>
                                    </div>
                                </template>
                                <template x-if="item.status == 2">
                                    <div class="mt-1 flex flex-col gap-0.5">
                                        <span class="text-[10px] text-success font-bold">
                                            <i class="fas fa-check-circle"></i> Pelunasan tercatat: <span
                                                x-text="item.tanggal_pembayaran"></span>
                                        </span>
                                        <span class="text-[9px] text-base-content/50 font-medium">
                                            Metode penerimaan: <span
                                                x-text="item.pembayaran_via == 1 ? 'Transfer Bank' : 'Cash/Tunai'"></span>
                                        </span>
                                    </div>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-center space-x-1 whitespace-nowrap">
                                <button @click="openDetailModal(item)"
                                    class="text-primary hover:text-primary hover:underline text-xs font-bold uppercase tracking-wider mr-2 transition-all">Lihat
                                    Detail</button>
                                <template x-if="item.status == 0">
                                    <button @click="chatWhatsApp(item)" :disabled="isLoading"
                                        class="disabled:opacity-50 disabled:cursor-not-allowed btn btn-success px-3 py-1.5 text-[11px] rounded-md">
                                        <i class="fab fa-whatsapp"></i> Kirim WA
                                    </button>
                                </template>
                                <template x-if="item.status == 0 || item.status == 1">
                                    <div class="inline-flex gap-1">
                                        <button @click="prosesBayarSiswa(item)" :disabled="isLoading"
                                            class="disabled:opacity-50 disabled:cursor-not-allowed btn btn-primary px-3 py-1.5 text-[11px] rounded-md">
                                            <i class="fas fa-hand-holding-usd"></i> Catat Bayar
                                        </button>
                                        <button @click="ubahKeLunas(item)" :disabled="isLoading"
                                            class="disabled:opacity-50 disabled:cursor-not-allowed btn-sacred px-3 py-1.5 text-[11px] rounded-md">
                                            <i class="fas fa-check"></i> Set Lunas
                                        </button>
                                    </div>
                                </template>
                                <template x-if="item.status == 2">
                                    <a :href="buildStrukUrl(item)" target="_blank"
                                        class="btn btn-accent px-3 py-1.5 text-[11px] rounded-md">
                                        <i class="fas fa-print"></i> Cetak Bukti
                                    </a>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="displayedSummaries.length === 0">
                        <td colspan="4"
                            class="px-6 py-12 text-center text-base-content/50 italic font-medium">Data
                            tidak
                            ditemukan!</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>

    <template x-if="!isDesktop">
        <div class="space-y-4 relative">
            <template x-for="item in displayedSummaries" :key="item.no_hp">
                <div
                    class="bg-base-200 p-4 rounded-xl border border-base-300 space-y-3">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <span class="text-sm font-bold text-base-content font-mono"
                                x-text="item.no_hp"></span>
                            <span class="block text-[11px] text-primary font-semibold mt-0.5 truncate"
                                x-text="item.siswa_names"></span>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 rounded font-mono font-bold text-xs"
                            :class="{
                                'bg-red-50 dark:bg-red-900/20 text-red-600': item.status == 0,
                                'bg-orange-50 dark:bg-orange-900/20 text-orange-600': item.status == 1,
                                'bg-success/10 text-success': item.status == 2
                            }">
                            Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.total_akhir)"></span>
                        </span>
                    </div>
                    <div
                        class="text-xs space-y-1 bg-base-100 p-2.5 rounded-lg border font-medium text-base-content/70">
                        <p class="truncate"><span
                                class="text-base-content/50 font-bold text-[10px] uppercase block">Administrasi:</span>
                            <span x-text="item.gabungan_keterangan || 'Belum ada keterangan tagihan.'"></span>
                        </p>
                        <template x-if="item.nominal_diskon > 0">
                            <p class="text-red-500 font-bold text-[11px] pt-1">Potongan Aktif: <span
                                    x-text="formatCurrency(item.nominal_diskon)"></span></p>
                        </template>
                        <div class="pt-2 border-t mt-1">
                            <div class="flex items-center justify-between text-[10px] font-black mb-1"
                                :class="{
                                    'text-red-500': item.status == 0,
                                    'text-amber-600 dark:text-amber-400': item.status == 1,
                                    'text-success': item.status == 2
                                }">
                                <span><i class="fas fa-chart-simple mr-1"></i>Progres Bayar</span>
                                <span x-text="item.paid_percent + '%'"></span>
                            </div>
                            <div class="w-full h-2.5 bg-base-300 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="{
                                        'bg-red-400': item.status == 0,
                                        'bg-amber-500': item.status == 1,
                                        'bg-success': item.status == 2
                                    }"
                                    :style="'width: ' + item.paid_percent + '%'"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1.5 gap-2">
                                <span class="text-[10px] font-bold text-success truncate"
                                    x-text="'Masuk: ' + formatCurrency(item.total_sudah_dibayar)"></span>
                                <span class="text-[10px] font-bold text-red-500 dark:text-red-400 truncate"
                                    x-text="'Sisa: ' + formatCurrency(item.remaining_amount)"></span>
                            </div>
                            <span class="block text-[9px] text-base-content/50 mt-1.5"
                                x-text="item.status == 2 ? 'Pelunasan: ' + item.tanggal_pembayaran : 'Periode input: ' + item.tanggal_format"></span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <button @click="openDetailModal(item)"
                            class="flex-1 bg-base-300 py-2.5 rounded-lg text-xs font-bold transition-all active:scale-95">Detail</button>
                        <template x-if="item.status == 0">
                            <button @click="chatWhatsApp(item)" :disabled="isLoading"
                                class="disabled:opacity-50 flex-1 bg-green-500 text-white py-2.5 rounded-lg text-xs font-bold transition-all active:scale-95"><i
                                    class="fab fa-whatsapp mr-1"></i>WA</button>
                        </template>
                        <template x-if="item.status == 0 || item.status == 1">
                            <button @click="prosesBayarSiswa(item)" :disabled="isLoading"
                                class="disabled:opacity-50 flex-1 bg-primary text-white py-2.5 rounded-lg text-xs font-bold transition-all active:scale-95">Bayar</button>
                            <button @click="ubahKeLunas(item)" :disabled="isLoading"
                                class="disabled:opacity-50 flex-1 bg-success text-white py-2.5 rounded-lg text-xs font-bold transition-all active:scale-95">Ke
                                Lunas</button>
                        </template>
                        <template x-if="item.status == 2">
                            <a :href="buildStrukUrl(item)" target="_blank"
                                class="flex-1 text-center bg-purple-600 text-white py-2.5 rounded-lg text-xs font-bold transition-all active:scale-95"><i
                                    class="fas fa-print mr-1"></i>Struk</a>
                        </template>
                    </div>
                </div>
            </template>
            <div x-show="displayedSummaries.length === 0"
                class="text-center text-xs text-base-content/50 italic py-8">Data tidak ditemukan!</div>
        </div>
    </template>

    <template x-if="showDetailModal">
        <div class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            x-transition>
            <div @click="showDetailModal = false" class="absolute inset-0"></div>
            <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border transform transition-all"
                @click.stop>
                <div
                    class="p-5 border-b flex justify-between items-center bg-base-200">
                    <h3 class="min-w-0 font-bold text-base-content flex items-center gap-2 text-base md:text-lg">
                        <i class="fas fa-info-circle text-primary shrink-0"></i> <span class="truncate">Rincian Tagihan Anggota Keluarga</span>
                    </h3>
                    <button @click="showDetailModal = false"
                        class="shrink-0 text-base-content/50 hover:text-base-content/70 p-2.5 hover:bg-base-300 rounded-xl transition-all"><i
                            class="fas fa-times fa-lg"></i></button>
                </div>
                <div
                    class="p-6 space-y-5 text-sm text-base-content overflow-y-auto max-h-[70vh] custom-scrollbar">
                    <div x-show="isLoadingDetail"
                        class="rounded-xl border border-dashed border-success/40 bg-success/10 px-4 py-5 text-center">
                        <i class="fas fa-circle-notch fa-spin text-success text-lg"></i>
                        <p class="mt-2 text-sm font-semibold text-success">Memuat rincian
                            pembayaran keluarga...</p>
                    </div>
                    <div class="bg-base-200 p-4 rounded-xl border">
                        <span class="text-xs text-base-content/50 font-bold uppercase block tracking-wider">Grup Nomor HP
                            Utama</span>
                        <p class="text-lg font-mono font-bold text-base-content mt-0.5"
                            x-text="activeDetail.no_hp"></p>
                    </div>
                    <div>
                        <span class="text-xs text-base-content/50 font-bold uppercase block tracking-wider mb-2">Rincian Siswa
                            &
                            Item Komponen Tagihan</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="raw in activeDetail.raw_items" :key="raw.id">
                                <div
                                    class="p-3.5 bg-base-100 rounded-xl border shadow-sm flex justify-between items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-base-content truncate"
                                            x-text="raw.siswa ? raw.siswa.name : 'N/A'"></p>
                                        <p class="text-xs text-base-content/60 truncate mt-0.5"
                                            x-text="'Kelas: ' + (raw.siswa ? raw.siswa.kelas : '-') + ' | ' + raw.keterangan">
                                        </p>
                                    </div>
                                    <span class="font-mono font-bold text-sm text-base-content shrink-0"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(raw.harga)"></span>
                                </div>
                            </template>
                            <template
                                x-if="!isLoadingDetail && (!activeDetail.raw_items || activeDetail.raw_items.length === 0)">
                                <div
                                    class="col-span-full text-xs text-base-content/50 italic text-center py-6 bg-base-100 rounded-xl border border-dashed border-base-300">
                                    Belum ada item tagihan yang bisa ditampilkan.
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="bg-base-200 p-4 rounded-xl space-y-2 border">
                        <div class="flex justify-between items-center text-xs md:text-sm font-semibold">
                            <span>Total Tagihan Kotor:</span>
                            <span class="font-mono text-base-content"
                                x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(activeDetail.total_harga)"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs md:text-sm font-semibold">
                            <span>Total Sudah Diterima:</span>
                            <span class="font-mono text-success"
                                x-text="formatCurrency(activeDetail.total_sudah_dibayar || 0)"></span>
                        </div>
                        <div>
                            <div class="w-full h-2.5 bg-base-300 rounded-full overflow-hidden">
                                <div class="h-full bg-success rounded-full transition-all duration-500"
                                    :style="'width: ' + (activeDetail.total_akhir > 0 ? Math.min(100, Math.round((activeDetail
                                        .total_sudah_dibayar || 0) / activeDetail.total_akhir * 100)) : 0) + '%'">
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-success mt-1 text-right"
                                x-text="(activeDetail.total_akhir > 0 ? Math.min(100, Math.round((activeDetail.total_sudah_dibayar || 0) / activeDetail.total_akhir * 100)) : 0) + '% dari total sudah masuk'">
                            </p>
                        </div>
                        <template x-if="activeDetail.nominal_diskon > 0">
                            <div
                                class="flex justify-between items-center text-red-500 font-bold text-xs border-t pt-2">
                                <span x-text="'Potongan berlaku (' + activeDetail.keterangan_diskon + '):'"></span>
                                <span class="font-mono"
                                    x-text="'- Rp ' + new Intl.NumberFormat('id-ID').format(activeDetail.nominal_diskon)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between items-center text-xs md:text-sm font-semibold">
                            <span>Sisa Kewajiban:</span>
                            <span class="font-mono text-amber-600 dark:text-amber-400"
                                x-text="formatCurrency(Math.max((activeDetail.total_akhir || 0) - (activeDetail.total_sudah_dibayar || 0), 0))"></span>
                        </div>
                        <div
                            class="border-t-2 border-dashed pt-2 flex justify-between items-center font-black text-sm md:text-base text-primary">
                            <span>Total Bersih Wajib Bayar:</span>
                            <span class="font-mono"
                                x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(activeDetail.total_akhir)"></span>
                        </div>
                    </div>
                    <div class="border-t pt-4">
                        <span class="text-xs text-base-content/50 font-bold uppercase block tracking-wider mb-2">Riwayat
                            Pembayaran Angsuran Masuk</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="det in activeDetail.payment_details" :key="det.id">
                                <div
                                    class="p-3 bg-success/10 rounded-xl flex justify-between items-center text-xs border border-success/40">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="font-bold text-success truncate"
                                            x-text="det.keterangan"></p>
                                        <p class="text-[10px] text-base-content/50 mt-0.5"
                                            x-text="new Date(det.created_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'})">
                                        </p>
                                    </div>
                                    <span class="font-mono font-bold text-success shrink-0"
                                        x-text="'+ Rp ' + new Intl.NumberFormat('id-ID').format(det.pembayaran)"></span>
                                </div>
                            </template>
                            <template x-if="activeDetail.payment_details && activeDetail.payment_details.length === 0">
                                <div
                                    class="col-span-full text-xs text-base-content/50 italic text-center py-6 bg-base-100 rounded-xl border border-dashed border-base-300">
                                    Belum ada cicilan atau setoran dana masuk.</div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end bg-base-200">
                    <button type="button" @click="showDetailModal = false"
                        class="px-5 py-2 text-sm bg-base-300 hover:bg-base-300 font-bold rounded-xl transition-all">Tutup
                        Rincian</button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="showDiskonModal">
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            x-transition>
            <div @click="showDiskonModal = false" class="absolute inset-0"></div>
            <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border transform transition-all"
                @click.stop>
                <div
                    class="p-5 border-b flex justify-between items-center bg-base-200">
                    <h3 class="min-w-0 font-bold text-base-content flex items-center gap-2 text-base md:text-lg">
                        <i class="fas fa-tags text-purple-500 shrink-0"></i>
                        <span class="truncate">Kelola Potongan Diskon <span class="hidden sm:inline">(Spesifik & Universal)</span></span>
                    </h3>
                    <button @click="showDiskonModal = false"
                        class="shrink-0 text-base-content/50 hover:text-base-content/70 p-2.5 hover:bg-base-300 rounded-xl transition-all"><i
                            class="fas fa-times fa-lg"></i></button>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto max-h-[80vh] custom-scrollbar">
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider border-b pb-1"
                            x-text="diskonForm.id ? 'Edit Aturan Diskon' : 'Tambah Diskon Baru'"></h4>
                        <form @submit.prevent="simpanDiskon" class="space-y-4">
                            <div
                                class="bg-base-200 p-3 rounded-xl border flex items-center justify-between">
                                <span class="text-xs font-bold text-base-content/80">Jadikan Diskon
                                    Universal
                                    (Semua Siswa)</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="diskonForm.is_universal"
                                        :disabled="diskonForm.id !== null" class="sr-only peer">
                                    <div
                                        class="w-9 h-5 bg-base-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-base-100 after:border-base-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600">
                                    </div>
                                </label>
                            </div>
                            <div class="relative" x-data="{ openHpSearch: false }" x-show="!diskonForm.is_universal">
                                <label class="block text-xs font-bold text-base-content/60 mb-1">Pilih
                                    Keluarga
                                    Terdaftar (No HP)</label>
                                <div class="relative mt-1">
                                    <input type="text" x-model="hpSearchModal" @focus="openHpSearch = true"
                                        @click.away="openHpSearch = false" :readonly="diskonForm.id !== null"
                                        placeholder="Ketik No HP / Nama Anak untuk mencari..."
                                        class="block w-full rounded-xl border border-base-300 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all pl-8 py-2.5 focus:outline-none">
                                    <i class="fas fa-search absolute left-3 top-3.5 text-base-content/50 text-xs"></i>
                                </div>
                                <div x-show="openHpSearch && filteredFamiliesForModal.length > 0 && !diskonForm.id"
                                    class="absolute z-[120] w-full mt-1 bg-base-100 border rounded-xl shadow-xl max-h-40 overflow-y-auto divide-y"
                                    x-transition>
                                    <template x-for="fam in filteredFamiliesForModal" :key="fam.no_hp">
                                        <button type="button"
                                            @click="diskonForm.no_hp = fam.no_hp; hpSearchModal = fam.no_hp + ' - (' + fam.siswa_names + ')'; openHpSearch = false"
                                            class="w-full text-left px-4 py-2.5 text-xs hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors flex flex-col font-medium">
                                            <span x-text="fam.no_hp"
                                                class="font-bold font-mono text-base-content"></span>
                                            <span x-text="fam.siswa_names"
                                                class="text-[10px] text-base-content/50 truncate w-full mt-0.5"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-950/20 p-3 rounded-xl border border-purple-200 dark:border-purple-900 text-xs text-purple-800 dark:text-purple-300 font-semibold flex items-center gap-2"
                                x-show="diskonForm.is_universal">
                                <i class="fas fa-bullhorn text-sm shrink-0"></i>
                                <span>Diskon Universal aktif akan otomatis memotong tagihan akhir <strong>seluruh
                                        siswa</strong> tanpa terkecuali.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-base-content/60 mb-1">Nominal
                                    Diskon
                                    (Rp)</label>
                                <input type="number" x-model.number="diskonForm.diskon" required
                                    class="block w-full rounded-xl border-base-300 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all py-2.5 focus:outline-none"
                                    placeholder="Masukkan nilai rupiah potongan...">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-base-content/60 mb-1">Nama
                                    Potongan
                                    / Keterangan Event</label>
                                <input type="text" x-model="diskonForm.keterangan" required
                                    class="block w-full rounded-xl border-base-300 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all py-2.5 focus:outline-none"
                                    placeholder="Contoh: Diskon Ramadhan / Kakak Beradik">
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button type="submit"
                                    class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 active:scale-95 transition-all">
                                    <span x-text="diskonForm.id ? 'Update Aturan' : 'Terapkan Aturan'"></span>
                                </button>
                                <button type="button" x-show="diskonForm.id" @click="resetDiskonForm"
                                    class="px-4 py-2 border border-base-300 rounded-xl text-sm font-medium transition-colors hover:bg-base-200">Batal</button>
                            </div>
                        </form>
                    </div>
                    <div
                        class="flex flex-col border-t md:border-t-0 md:border-l pt-4 md:pt-0 md:pl-6">
                        <h4 class="text-xs font-bold text-base-content/50 uppercase tracking-wider mb-3">
                            Daftar
                            Aturan Diskon Terdata</h4>
                        <div class="space-y-2 overflow-y-auto max-h-[280px] pr-1 custom-scrollbar">
                            <template x-for="d in diskons" :key="d.id">
                                <div class="p-3 bg-base-200 rounded-xl flex justify-between items-center border border-base-300 hover:border-purple-300 dark:hover:border-purple-500 transition-all"
                                    :class="d.no_hp === null ? 'ring-2 ring-purple-500 bg-purple-50/20 dark:bg-purple-950/10' :
                                        ''">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-bold font-mono text-base-content"
                                                x-text="d.no_hp === null ? 'GLOBAL / UNIVERSAL' : d.no_hp"></span>
                                            <template x-if="d.no_hp === null">
                                                <span
                                                    class="bg-purple-600 text-white text-[8px] font-black uppercase px-1 rounded tracking-wider">Massal</span>
                                            </template>
                                        </div>
                                        <div class="text-[10px] text-base-content/50 font-semibold truncate mt-0.5"
                                            x-text="getKeluargaLabelByHp(d.no_hp)"></div>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            <span class="text-xs font-mono font-black text-red-500 dark:text-red-400"
                                                x-text="'- Rp ' + new Intl.NumberFormat('id-ID').format(d.diskon)"></span>
                                            <span
                                                class="text-[9px] bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 px-1.5 py-0.5 rounded font-bold"
                                                x-text="d.keterangan || 'Potongan'"></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <button @click="editDiskon(d)"
                                            class="p-2.5 text-primary hover:bg-primary/10 rounded-lg transition-colors"><i
                                                class="fas fa-edit text-xs"></i></button>
                                        <button @click="hapusDiskon(d.id)"
                                            class="p-2.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"><i
                                                class="fas fa-trash text-xs"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="diskons.length === 0">
                                <p
                                    class="text-xs text-base-content/50 italic text-center py-8 font-medium">
                                    Belum ada master potongan diskon yang dibuat.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-if="showAddModal">
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            x-transition>
            <div @click="showAddModal = false" class="absolute inset-0"></div>
            <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border transform transition-all"
                @click.stop>
                <div
                    class="p-5 border-b flex justify-between items-center bg-base-200">
                    <h3 class="min-w-0 font-bold text-base-content flex items-center gap-2 text-base md:text-lg">
                        <i class="fas fa-file-invoice-dollar text-primary shrink-0"></i> <span class="truncate">Buat Input Tagihan Manual Baru</span>
                    </h3>
                    <button @click="showAddModal = false"
                        class="shrink-0 text-base-content/50 hover:text-base-content/70 p-2.5 hover:bg-base-300 rounded-xl transition-all"><i
                            class="fas fa-times fa-lg"></i></button>
                </div>
                <form @submit.prevent="simpanTagihan" class="p-6 space-y-5 overflow-y-auto max-h-[75vh] custom-scrollbar">
                    <div class="relative" x-data="{ openSearch: false }">
                        <label class="block text-xs font-bold text-base-content/50 uppercase tracking-wider mb-1.5">Pilih
                            Target
                            Siswa Bimbel</label>
                        <div class="relative mt-1">
                            <input type="text" x-model="siswaSearchModal" @focus="openSearch = true"
                                @click.away="openSearch = false"
                                placeholder="Ketik nama lengkap siswa untuk memfilter..."
                                class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-base-300 focus:ring-2 focus:ring-primary focus:border-primary text-sm transition-all focus:outline-none">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/50">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                        </div>
                        <div x-show="openSearch && filteredSiswasForModal.length > 0"
                            class="absolute z-[120] w-full mt-1 bg-base-100 border rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y"
                            x-transition>
                            <template x-for="s in filteredSiswasForModal" :key="s.id">
                                <button type="button"
                                    @click="form.id_siswa = s.id; siswaSearchModal = s.name; openSearch = false"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-primary/10 transition-colors flex items-center gap-2 font-medium">
                                    <i class="fas fa-check-circle text-xs text-primary shrink-0"></i>
                                    <span class="min-w-0 flex-1 truncate"><span x-text="s.name" class="font-bold"></span> — <span x-text="s.kelas || 'N/A'"
                                        class="text-xs text-base-content/50 font-medium"></span></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-base-content/50 uppercase tracking-wider mb-1.5">Jenis
                                Tagihan</label>
                            <select @change="applyPaket($event.target.value)"
                                class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-all focus:outline-none">
                                <option value="">-- Tanpa paket: buku, denda, kegiatan (boleh berulang) --
                                </option>
                                <template x-for="p in pakets" :key="p.id">
                                    <option :value="p.id"
                                        x-text="'Paket: ' + p.nama_paket + ' (Rp ' + new Intl.NumberFormat('id-ID').format(p.harga) + ')'">
                                    </option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-base-content/50 uppercase tracking-wider mb-1.5">Nominal
                                Harga Tagihan (Rp)</label>
                            <input type="number" x-model.number="form.harga" required
                                class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-all focus:outline-none"
                                placeholder="Masukkan angka tarif tagihan...">
                        </div>
                    </div>
                    <template x-if="peringatanDuplikat">
                        <div
                            class="rounded-xl border-2 border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-950/30 p-4">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
                                <div class="min-w-0 text-xs leading-relaxed">
                                    <p class="font-black text-red-700 dark:text-red-300 uppercase tracking-wider mb-1">
                                        Stop — Ini Akan Jadi Tagihan Ganda
                                    </p>
                                    <p class="text-red-800 dark:text-red-200 font-semibold"
                                        x-text="peringatanDuplikat"></p>
                                    <p class="text-red-700/90 dark:text-red-300/90 mt-2 font-medium">
                                        Kalau uangnya sudah diterima, jangan buat tagihan baru — tutup modal ini,
                                        lalu tekan <span class="font-black">Catat Bayar</span> pada tagihan yang sudah
                                        ada.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!form.id_paket && form.id_siswa">
                        <div
                            class="rounded-xl border border-primary/40 bg-primary/10 p-3.5 text-xs text-primary flex items-start gap-2.5 leading-relaxed">
                            <i class="fas fa-circle-info text-primary mt-0.5"></i>
                            <span>
                                <span class="font-black">Tagihan bebas (tanpa paket).</span>
                                Cocok untuk buku, denda, atau kegiatan. Jenis ini boleh dibuat berkali-kali untuk siswa
                                yang sama
                                dan tidak akan pernah diduplikat oleh Penagihan Massal.
                            </span>
                        </div>
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-base-content/50 uppercase tracking-wider mb-1.5">Keterangan
                            Catatan Tagihan</label>
                        <textarea x-model="form.keterangan" rows="3" required
                            class="w-full rounded-xl border border-base-300 p-3.5 bg-base-100 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-all focus:outline-none"
                            placeholder="Tuliskan alasan atau keterangan perihal pembuatan tagihan manual ini..."></textarea>
                    </div>
                    <div class="pt-3 flex justify-end gap-2.5 border-t">
                        <button type="button" @click="showAddModal = false"
                            class="px-5 py-2 text-sm border border-base-300 rounded-xl hover:bg-base-200 transition-all">Batal</button>
                        <button type="submit" :disabled="isLoading"
                            class="px-6 py-2 text-sm bg-primary text-white rounded-xl hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed font-bold transition-all flex items-center gap-2 shadow-md active:scale-95">
                            <span>Simpan Tagihan Baru</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <template x-if="showPaketModal">
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            x-transition>
            <div @click="showPaketModal = false" class="absolute inset-0"></div>
            <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border transform transition-all"
                @click.stop>
                <div
                    class="p-5 border-b flex justify-between items-center bg-base-200">
                    <h3 class="min-w-0 font-bold text-base-content flex items-center gap-2 text-base md:text-lg"><i
                            class="fas fa-box text-purple-500 shrink-0"></i> <span class="truncate">Kelola Paket Master Pembayaran Bimbel</span></h3>
                    <button @click="showPaketModal = false"
                        class="shrink-0 text-base-content/50 hover:text-base-content/70 p-2.5 hover:bg-base-300 rounded-xl transition-all"><i
                            class="fas fa-times fa-lg"></i></button>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto max-h-[80vh] custom-scrollbar">
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider border-b pb-1"
                            x-text="paketForm.id ? 'Edit Data Paket' : 'Tambah Paket Baru'"></h4>
                        <form @submit.prevent="savePaket" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-base-content/60 mb-1">Nama Paket
                                    Program</label>
                                <input type="text" x-model="paketForm.nama_paket" required
                                    class="block w-full rounded-xl border border-base-300 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all py-2.5 focus:outline-none"
                                    placeholder="Contoh: SPP Bulanan Reguler">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-base-content/60 mb-1">Harga
                                    Tarif
                                    Paket (Rp)</label>
                                <input type="number" x-model.number="paketForm.harga" required
                                    class="block w-full rounded-xl border border-base-300 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all py-2.5 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-base-content/60 mb-1">Jumlah
                                    Jatah
                                    Pertemuan Sesi</label>
                                <input type="number" x-model.number="paketForm.pertemuan" required
                                    class="block w-full rounded-xl border border-base-300 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all py-2.5 focus:outline-none">
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button type="submit"
                                    class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 active:scale-95 transition-all">
                                    <span x-text="paketForm.id ? 'Update Aturan Paket' : 'Simpan Paket Baru'"></span>
                                </button>
                                <button type="button" x-show="paketForm.id" @click="resetPaketForm"
                                    class="px-4 py-2 border border-base-300 rounded-xl text-sm font-medium transition-colors hover:bg-base-200">Batal</button>
                            </div>
                        </form>
                    </div>
                    <div
                        class="flex flex-col border-t md:border-t-0 md:border-l pt-4 md:pt-0 md:pl-6">
                        <h4 class="text-xs font-bold text-base-content/50 uppercase tracking-wider mb-3">
                            Daftar
                            Paket Master Terdaftar</h4>
                        <div class="space-y-2 overflow-y-auto max-h-[280px] pr-1 custom-scrollbar">
                            <template x-for="p in pakets" :key="p.id">
                                <div
                                    class="p-3 bg-base-200 rounded-xl flex justify-between items-center border border-base-300 hover:border-purple-300 dark:hover:border-purple-500 transition-all">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <div class="text-sm font-bold text-base-content truncate"
                                            x-text="p.nama_paket"></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="text-xs font-mono font-bold text-purple-600 dark:text-purple-400"
                                                x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(p.harga)"></span>
                                            <span
                                                class="text-[9px] bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 px-1.5 py-0.5 rounded-md font-bold"
                                                x-text="p.pertemuan + ' Sesi'"></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <button @click="editPaket(p)"
                                            class="p-2.5 text-primary hover:bg-primary/10 rounded-lg transition-colors"><i
                                                class="fas fa-edit text-xs"></i></button>
                                        <button @click="deletePaket(p.id)"
                                            class="p-2.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"><i
                                                class="fas fa-trash text-xs"></i></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
