<div class="bg-white dark:bg-gray-800 p-4 md:p-6 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 transition-all duration-200"
    x-data="pembayaranHandler({{ $pembayaranSummaries->toJson() }}, {{ $allSiswas->toJson() }}, {{ $pakets->toJson() }}, {{ $diskons->toJson() }})">

    <div
        class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4 border-b border-gray-50 dark:border-gray-700/50 pb-4">
        <div>
            <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-wallet text-emerald-500 animate-pulse"></i>
                Ringkasan Tagihan Siswa
            </h3>
            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">Total terdaftar tunggakan: <span
                    class="font-semibold text-emerald-600 dark:text-emerald-400" x-text="filteredSummaries.length"></span>
                HP Keluarga</p>
        </div>
        <div class="flex flex-wrap gap-2 w-full lg:w-auto">
            <button @click="exportPdf()" :disabled="isLoading"
                class="flex-1 lg:flex-none justify-center bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition-all flex items-center gap-2 shadow-sm active:scale-95">
                <i class="fas fa-file-pdf"></i> <span class="hidden sm:inline">Export</span> PDF
            </button>
            <button @click="openDiskonManagerModal()" :disabled="isLoading"
                class="flex-1 lg:flex-none justify-center bg-purple-500 hover:bg-purple-600 disabled:opacity-50 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition-all flex items-center gap-2 shadow-sm active:scale-95">
                <i class="fas fa-tags"></i> Kelola Diskon
            </button>
            <button @click="prosesPenagihanMassal()" :disabled="isLoading"
                class="flex-1 lg:flex-none justify-center bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition-all flex items-center gap-2 shadow-sm active:scale-95">
                <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-file-invoice-dollar'"></i>
                <span>Penagihan Massal</span>
            </button>
            <button @click="openPaketModal()" :disabled="isLoading"
                class="flex-1 lg:flex-none justify-center bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition-all flex items-center gap-2 shadow-sm active:scale-95">
                <i class="fas fa-box"></i> <span class="hidden sm:inline">Kelola</span> Paket
            </button>
            <button @click="openAddPembayaran()" :disabled="isLoading"
                class="flex-1 lg:flex-none justify-center bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition-all flex items-center gap-2 shadow-sm active:scale-95">
                <i class="fas fa-plus"></i> Tagihan
            </button>
            <button @click="lunaskanSemua()" :disabled="isLoading"
                class="w-full lg:w-auto justify-center bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition-all flex items-center gap-2 shadow-sm active:scale-95">
                <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-check-double'"></i>
                <span>Selesaikan Seluruh Status</span>
            </button>
        </div>
    </div>

    <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
        <div class="w-full">
            <label
                class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Cari
                Nama / No HP / Keterangan</label>
            <div class="relative">
                <input type="text" x-model="filterSearch" placeholder="Ketik kata kunci..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 pl-9 transition-all">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
            </div>
        </div>
        <div class="w-full">
            <label
                class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Filter
                Bulan</label>
            <select x-model="filterBulan"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
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
                class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Filter
                Status Kelayakan</label>
            <div
                class="flex flex-wrap items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-lg border dark:border-gray-600">
                <label
                    class="flex-1 inline-flex items-center justify-center text-xs dark:text-gray-300 cursor-pointer px-2 py-1 rounded md:hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <input type="radio" x-model="filterStatus" value="all"
                        class="text-emerald-500 focus:ring-0 w-3 h-3">
                    <span class="ml-1.5 font-medium">Semua</span>
                </label>
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
                    class="flex-1 inline-flex items-center justify-center text-xs text-emerald-500 font-bold cursor-pointer px-2 py-1 rounded md:hover:bg-emerald-50 dark:hover:bg-emerald-950/20 transition-all">
                    <input type="radio" x-model="filterStatus" value="2"
                        class="text-emerald-500 focus:ring-0 w-3 h-3">
                    <span class="ml-1.5">Lunas</span>
                </label>
            </div>
        </div>
    </div>

    <div class="hidden md:block overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-xl relative">
        <div x-show="isLoading"
            class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
            <i class="fas fa-circle-notch fa-spin fa-2x text-emerald-500"></i>
        </div>

        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">No
                        HP / Anggota Keluarga</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                        Tagihan Akhir</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                        Keterangan Akumulasi</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                        Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="item in filteredSummaries" :key="item.no_hp">
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/20 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-white font-mono"
                                x-text="item.no_hp"></span>
                            <span class="block text-[11px] text-blue-600 dark:text-blue-400 font-semibold mt-0.5"
                                x-text="item.siswa_names"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="px-2 py-1 rounded-md font-mono font-bold text-sm max-w-max"
                                    :class="{
                                        'bg-red-50 dark:bg-red-900/20 text-red-600': item.status == 0,
                                        'bg-orange-50 dark:bg-orange-900/20 text-orange-600': item.status == 1,
                                        'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600': item.status == 2
                                    }">
                                    Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.total_akhir)"></span>
                                </span>
                                <template x-if="item.nominal_diskon > 0">
                                    <div class="flex items-center gap-1 mt-1 text-[10px] text-red-500 font-bold">
                                        <i class="fas fa-percent text-[9px]"></i>
                                        <span
                                            x-text="'Diskon: Rp ' + new Intl.NumberFormat('id-ID').format(item.nominal_diskon)"></span>
                                    </div>
                                </template>
                                <span class="text-[10px] text-gray-400 mt-1 font-medium"
                                    x-text="'Sudah Dibayar: Rp ' + new Intl.NumberFormat('id-ID').format(item.total_sudah_dibayar)"></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate font-medium"
                                x-text="item.gabungan_keterangan || '-'"></p>
                            <template x-if="item.status != 2">
                                <span class="text-[9px] text-gray-400 font-medium block mt-1"
                                    x-text="item.tanggal_format"></span>
                            </template>
                            <template x-if="item.status == 2">
                                <div class="mt-1 flex flex-col gap-0.5">
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">
                                        <i class="fas fa-check-circle"></i> Lunas: <span
                                            x-text="item.tanggal_pembayaran"></span>
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-medium">
                                        Metode: <span
                                            x-text="item.pembayaran_via == 1 ? 'Transfer Bank' : 'Cash/Tunai'"></span>
                                    </span>
                                </div>
                            </template>
                        </td>
                        <td class="px-6 py-4 text-center space-x-1 whitespace-nowrap">
                            <button @click="openDetailModal(item)"
                                class="text-blue-500 hover:text-blue-600 hover:underline text-xs font-bold uppercase tracking-wider mr-2 transition-all">Detail</button>

                            <template x-if="item.status == 0">
                                <button @click="chatWhatsApp(item)" :disabled="isLoading"
                                    class="bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white px-3 py-1.5 rounded-md text-[11px] font-bold transition-all inline-flex items-center gap-1 shadow-sm active:scale-95">
                                    <i class="fab fa-whatsapp"></i> Chat WA
                                </button>
                            </template>

                            <template x-if="item.status == 0 || item.status == 1">
                                <div class="inline-flex gap-1">
                                    <button @click="prosesBayarSiswa(item)" :disabled="isLoading"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-3 py-1.5 rounded-md text-[11px] font-bold transition-all inline-flex items-center gap-1 shadow-sm active:scale-95">
                                        <i class="fas fa-hand-holding-usd"></i> Bayar
                                    </button>
                                    <button @click="ubahKeLunas(item)" :disabled="isLoading"
                                        class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-3 py-1.5 rounded-md text-[11px] font-bold transition-all inline-flex items-center gap-1 shadow-sm active:scale-95">
                                        <i class="fas fa-check"></i> Lunas
                                    </button>
                                </div>
                            </template>

                            <template x-if="item.status == 2">
                                <a :href="'/admin/pembayaran/struk/' + item.no_hp" target="_blank"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-md text-[11px] font-bold transition-all inline-flex items-center gap-1 shadow-sm active:scale-95">
                                    <i class="fas fa-print"></i> Cetak Struk
                                </a>
                            </template>
                        </td>
                    </tr>
                </template>
                <template x-if="filteredSummaries.length === 0">
                    <tr>
                        <td colspan="4"
                            class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 italic font-medium">Data
                            tidak ditemukan!</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="block md:hidden space-y-4 relative">
        <div x-show="isLoading"
            class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
            <i class="fas fa-circle-notch fa-spin fa-2x text-emerald-500"></i>
        </div>
        <template x-for="item in filteredSummaries" :key="item.no_hp">
            <div
                class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-xl border border-gray-100 dark:border-gray-700/70 space-y-3">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white font-mono"
                            x-text="item.no_hp"></span>
                        <span class="block text-[11px] text-blue-600 dark:text-blue-400 font-semibold mt-0.5"
                            x-text="item.siswa_names"></span>
                    </div>
                    <span class="px-2 py-0.5 rounded font-mono font-bold text-xs"
                        :class="{
                            'bg-red-50 dark:bg-red-900/20 text-red-600': item.status == 0,
                            'bg-orange-50 dark:bg-orange-900/20 text-orange-600': item.status == 1,
                            'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600': item.status == 2
                        }">
                        Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.total_akhir)"></span>
                    </span>
                </div>
                <div
                    class="text-xs space-y-1 bg-white dark:bg-gray-800 p-2.5 rounded-lg border dark:border-gray-700 font-medium text-gray-600 dark:text-gray-400">
                    <p class="truncate"><span
                            class="text-gray-400 dark:text-gray-500 font-bold text-[10px] uppercase block">Akumulasi:</span>
                        <span x-text="item.gabungan_keterangan || '-'"></span>
                    </p>
                    <template x-if="item.nominal_diskon > 0">
                        <p class="text-red-500 font-bold text-[11px] pt-1">Diskon Aktif: Rp <span
                                x-text="new Intl.NumberFormat('id-ID').format(item.nominal_diskon)"></span></p>
                    </template>
                    <div class="pt-1 border-t dark:border-gray-700 mt-1 flex justify-between text-[10px]">
                        <span
                            x-text="'Paid: Rp ' + new Intl.NumberFormat('id-ID').format(item.total_sudah_dibayar)"></span>
                        <span
                            x-text="item.status == 2 ? 'Lunas: ' + item.tanggal_pembayaran : item.tanggal_format"></span>
                    </div>
                </div>
                <div class="flex gap-1.5 pt-1">
                    <button @click="openDetailModal(item)"
                        class="flex-1 bg-gray-200 dark:bg-gray-700 dark:text-white py-2 rounded-lg text-xs font-bold transition-all active:scale-95">Detail</button>
                    <template x-if="item.status == 0">
                        <button @click="chatWhatsApp(item)"
                            class="flex-1 bg-green-500 text-white py-2 rounded-lg text-xs font-bold transition-all active:scale-95"><i
                                class="fab fa-whatsapp mr-1"></i>WA</button>
                    </template>
                    <template x-if="item.status == 0 || item.status == 1">
                        <button @click="prosesBayarSiswa(item)"
                            class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-xs font-bold transition-all active:scale-95">Bayar</button>
                        <button @click="ubahKeLunas(item)"
                            class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-xs font-bold transition-all active:scale-95">Ke
                            Lunas</button>
                    </template>
                    <template x-if="item.status == 2">
                        <a :href="'/admin/pembayaran/struk/' + item.no_hp" target="_blank"
                            class="flex-1 text-center bg-purple-600 text-white py-2 rounded-lg text-xs font-bold transition-all active:scale-95"><i
                                class="fas fa-print mr-1"></i>Struk</a>
                    </template>
                </div>
            </div>
        </template>
        <template x-if="filteredSummaries.length === 0">
            <p class="text-center text-xs text-gray-400 dark:text-gray-500 italic py-8">Data tidak ditemukan!</p>
        </template>
    </div>

    <div x-show="showDetailModal"
        class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showDetailModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden relative border dark:border-gray-700 transform transition-all"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i> Rincian Tagihan Keluarga
                </h3>
                <button @click="showDetailModal = false"
                    class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <div class="p-4 md:p-6 space-y-4 text-sm text-gray-800 dark:text-gray-200 overflow-y-auto max-h-[70vh]">
                <div>
                    <span class="text-xs text-gray-400 font-bold uppercase block tracking-wider">Nomor HP Utama</span>
                    <p class="text-base font-mono font-bold text-gray-900 dark:text-white"
                        x-text="activeDetail.no_hp"></p>
                </div>
                <div class="border-t dark:border-gray-700 pt-3">
                    <span class="text-xs text-gray-400 font-bold uppercase block tracking-wider mb-2">Rincian Siswa &
                        Item Tagihan</span>
                    <div class="space-y-2">
                        <template x-for="raw in activeDetail.raw_items" :key="raw.id">
                            <div
                                class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl border dark:border-gray-700/60 flex justify-between items-center gap-4">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-gray-900 dark:text-white truncate"
                                        x-text="raw.siswa ? raw.siswa.name : 'N/A'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5"
                                        x-text="'Kelas: ' + (raw.siswa ? raw.siswa.kelas : '-') + ' | ' + raw.keterangan">
                                    </p>
                                </div>
                                <span class="font-mono font-bold text-sm text-gray-900 dark:text-white shrink-0"
                                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(raw.harga)"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div
                    class="border-t dark:border-gray-700 pt-3 flex justify-between items-center font-bold text-xs md:text-sm">
                    <span>Total Tagihan Kotor:</span>
                    <span class="font-mono text-gray-900 dark:text-white"
                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(activeDetail.total_harga)"></span>
                </div>
                <template x-if="activeDetail.nominal_diskon > 0">
                    <div class="flex justify-between items-center text-red-500 font-bold text-xs">
                        <span x-text="'Potongan Diskon (' + activeDetail.keterangan_diskon + '):'"></span>
                        <span class="font-mono"
                            x-text="'- Rp ' + new Intl.NumberFormat('id-ID').format(activeDetail.nominal_diskon)"></span>
                    </div>
                </template>
                <div
                    class="border-t-2 border-dashed dark:border-gray-600 pt-2 flex justify-between items-center font-black text-sm md:text-base text-blue-600 dark:text-blue-400">
                    <span>Total Tagihan Bersih:</span>
                    <span class="font-mono"
                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(activeDetail.total_akhir)"></span>
                </div>
                <div class="border-t dark:border-gray-700 pt-3">
                    <span class="text-xs text-gray-400 font-bold uppercase block tracking-wider mb-2">Riwayat
                        Pembayaran Masuk (Details)</span>
                    <div class="space-y-1.5">
                        <template x-for="det in activeDetail.payment_details" :key="det.id">
                            <div
                                class="p-2 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-lg flex justify-between items-center text-xs border border-emerald-100/40 dark:border-emerald-900/30">
                                <div class="min-w-0 flex-1 pr-2">
                                    <p class="font-semibold text-emerald-800 dark:text-emerald-400 truncate"
                                        x-text="det.keterangan"></p>
                                    <p class="text-[10px] text-gray-400 mt-0.5"
                                        x-text="new Date(det.created_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'})">
                                    </p>
                                </div>
                                <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 shrink-0"
                                    x-text="'+ Rp ' + new Intl.NumberFormat('id-ID').format(det.pembayaran)"></span>
                            </div>
                        </template>
                        <template x-if="activeDetail.payment_details && activeDetail.payment_details.length === 0">
                            <p class="text-xs text-gray-400 italic text-center py-2 font-medium">Belum ada catatan
                                pembayaran cicilan masuk.</p>
                        </template>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t dark:border-gray-700 flex justify-end bg-gray-50 dark:bg-gray-900">
                <button type="button" @click="showDetailModal = false"
                    class="w-full sm:w-auto px-5 py-2 text-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 font-bold rounded-lg dark:text-white transition-colors">Tutup</button>
            </div>
        </div>
    </div>

    <div x-show="showDiskonModal"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showDiskonModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative border dark:border-gray-700 transform transition-all"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-tags text-purple-500"></i> Kelola Potongan Diskon Keluarga
                </h3>
                <button @click="showDiskonModal = false"
                    class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <div class="p-4 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto max-h-[80vh]">
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider border-b dark:border-gray-700 pb-1"
                        x-text="diskonForm.id ? 'Edit Aturan Diskon' : 'Tambah Diskon Baru'"></h4>
                    <form @submit.prevent="simpanDiskon" class="space-y-4">
                        <div class="relative" x-data="{ openHpSearch: false }">
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Pilih Keluarga
                                (No HP)</label>
                            <div class="relative mt-1">
                                <input type="text" x-model="hpSearchModal" @focus="openHpSearch = true"
                                    @click.away="openHpSearch = false" :readonly="diskonForm.id !== null"
                                    placeholder="Cari No HP / Nama Anak..."
                                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all pl-8">
                                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <div x-show="openHpSearch && filteredFamiliesForModal.length > 0 && !diskonForm.id"
                                class="absolute z-[120] w-full mt-1 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-xl max-h-40 overflow-y-auto"
                                x-transition>
                                <template x-for="fam in filteredFamiliesForModal" :key="fam.no_hp">
                                    <button type="button"
                                        @click="diskonForm.no_hp = fam.no_hp; hpSearchModal = fam.no_hp + ' - (' + fam.siswa_names + ')'; openHpSearch = false"
                                        class="w-full text-left px-3 py-2 text-xs hover:bg-purple-50 dark:hover:bg-purple-900/30 dark:text-white border-b last:border-0 dark:border-gray-700 transition-colors flex flex-col">
                                        <span x-text="fam.no_hp"
                                            class="font-bold font-mono text-gray-900 dark:text-white"></span>
                                        <span x-text="fam.siswa_names"
                                            class="text-[10px] text-gray-400 dark:text-gray-500 font-medium truncate w-full"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Nominal Diskon
                                (Rp)</label>
                            <input type="number" x-model.number="diskonForm.diskon" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                placeholder="Masukkan nominal rupiah...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Keterangan
                                Diskon</label>
                            <input type="text" x-model="diskonForm.keterangan"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                placeholder="Contoh: Diskon Kakak Beradik">
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" :disabled="isLoading"
                                class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-bold shadow-md flex items-center justify-center gap-2 active:scale-95 transition-all">
                                <i x-show="isLoading" class="fas fa-spinner fa-spin"></i>
                                <span x-text="diskonForm.id ? 'Update Data' : 'Simpan Diskon'"></span>
                            </button>
                            <button type="button" x-show="diskonForm.id" @click="resetDiskonForm"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium dark:text-white transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">Batal</button>
                        </div>
                    </form>
                </div>
                <div
                    class="flex flex-col border-t md:border-t-0 md:border-l dark:border-gray-700 pt-4 md:pt-0 md:pl-6">
                    <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Daftar
                        Diskon Aktif</h4>
                    <div class="space-y-2 overflow-y-auto max-h-[260px] pr-1 custom-scrollbar">
                        <template x-for="d in diskons" :key="d.id">
                            <div
                                class="p-3 bg-gray-50 dark:bg-gray-700/20 rounded-xl flex justify-between items-center border border-gray-100 dark:border-gray-700/60 hover:border-purple-300 dark:hover:border-purple-500 transition-all">
                                <div class="min-w-0 flex-1 pr-2">
                                    <div class="text-xs font-bold text-gray-800 dark:text-white font-mono"
                                        x-text="d.no_hp"></div>
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold truncate mt-0.5"
                                        x-text="getKeluargaLabelByHp(d.no_hp)"></div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="text-xs font-mono font-black text-red-500 dark:text-red-400"
                                            x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(d.diskon)"></span>
                                        <span
                                            class="text-[9px] bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 px-1 py-0.5 rounded font-bold"
                                            x-text="d.keterangan || 'Potongan'"></span>
                                    </div>
                                </div>
                                <div class="flex gap-0.5 shrink-0">
                                    <button @click="editDiskon(d)" :disabled="isLoading"
                                        class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/30 rounded-lg transition-colors"><i
                                            class="fas fa-edit text-xs"></i></button>
                                    <button @click="hapusDiskon(d.id)" :disabled="isLoading"
                                        class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"><i
                                            class="fas text-xs"
                                            :class="isLoading ? 'fa-spinner fa-spin' : 'fa-trash'"></i></button>
                                </div>
                            </div>
                        </template>
                        <template x-if="diskons.length === 0">
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic text-center py-8 font-medium">
                                Belum ada potongan diskon yang dibuat.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showAddModal"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showAddModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative border dark:border-gray-700 transform transition-all"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white">Tambah Tagihan Manual</h3>
                <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <form @submit.prevent="simpanTagihan" class="p-4 md:p-6 space-y-4">
                <div class="relative" x-data="{ openSearch: false }">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Pilih Siswa</label>
                    <div class="relative mt-1">
                        <input type="text" x-model="siswaSearchModal" @focus="openSearch = true"
                            @click.away="openSearch = false" placeholder="Cari nama lengkap..."
                            class="block w-full pl-9 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <div x-show="openSearch && filteredSiswasForModal.length > 0"
                        class="absolute z-[120] w-full mt-1 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-xl max-h-40 overflow-y-auto"
                        x-transition>
                        <template x-for="s in filteredSiswasForModal" :key="s.id">
                            <button type="button"
                                @click="form.id_siswa = s.id; siswaSearchModal = s.name; openSearch = false"
                                class="w-full text-left px-4 py-2 text-xs hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:text-white border-b last:border-0 dark:border-gray-700 transition-colors">
                                <span x-text="s.name" class="font-bold"></span> - <span x-text="s.kelas || 'N/A'"
                                    class="text-[10px] text-gray-400 dark:text-gray-500 font-medium"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Gunakan Paket
                        (Opsional)</label>
                    <select @change="applyPaket($event.target.value)"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all">
                        <option value="">-- Pilih Paket Referensi --</option>
                        <template x-for="p in pakets" :key="p.id">
                            <option :value="p.id"
                                x-text="p.nama_paket + ' (Rp ' + new Intl.NumberFormat('id-ID').format(p.harga) + ')'">
                            </option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Harga (Rp)</label>
                    <input type="number" x-model.number="form.harga" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Keterangan
                        Tagihan</label>
                    <textarea x-model="form.keterangan" rows="3" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                        placeholder="Catatan perihal tagihan manual ini..."></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="showAddModal = false" :disabled="isLoading"
                        class="flex-1 sm:flex-none px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Batal</button>
                    <button type="submit" :disabled="isLoading"
                        class="flex-1 sm:flex-none px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-bold transition-all flex items-center justify-center gap-2 active:scale-95 shadow-md">
                        <i x-show="isLoading" class="fas fa-spinner fa-spin"></i>
                        <span x-text="isLoading ? 'Memproses...' : 'Simpan Tagihan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showPaketModal"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showPaketModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative border dark:border-gray-700 transform transition-all"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2"><i
                        class="fas fa-box text-purple-500"></i> Kelola Paket Pembayaran</h3>
                <button @click="showPaketModal = false" class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <div class="p-4 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto max-h-[80vh]">
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider border-b dark:border-gray-700 pb-1"
                        x-text="paketForm.id ? 'Edit Data Paket' : 'Tambah Paket Baru'"></h4>
                    <form @submit.prevent="savePaket" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Nama
                                Paket</label>
                            <input type="text" x-model="paketForm.nama_paket" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                placeholder="Contoh: SPP Bulanan">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Harga Paket
                                (Rp)</label>
                            <input type="number" x-model.number="paketForm.harga" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Jumlah
                                Pertemuan</label>
                            <input type="number" x-model.number="paketForm.pertemuan" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" :disabled="isLoading"
                                class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-bold shadow-md flex items-center justify-center gap-2 active:scale-95 transition-all">
                                <i x-show="isLoading" class="fas fa-spinner fa-spin"></i>
                                <span x-text="paketForm.id ? 'Update Paket' : 'Simpan Paket'"></span>
                            </button>
                            <button type="button" x-show="paketForm.id" @click="resetPaketForm"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium dark:text-white transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">Batal</button>
                        </div>
                    </form>
                </div>
                <div
                    class="flex flex-col border-t md:border-t-0 md:border-l dark:border-gray-700 pt-4 md:pt-0 md:pl-6">
                    <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Daftar
                        Paket Master</h4>
                    <div class="space-y-2 overflow-y-auto max-h-[260px] pr-1 custom-scrollbar">
                        <template x-for="p in pakets" :key="p.id">
                            <div
                                class="p-3 bg-gray-50 dark:bg-gray-700/20 rounded-xl flex justify-between items-center border border-gray-100 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500 transition-all">
                                <div class="min-w-0 flex-1 pr-2">
                                    <div class="text-xs font-bold text-gray-800 dark:text-white truncate"
                                        x-text="p.nama_paket"></div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-mono font-bold text-purple-600 dark:text-purple-400"
                                            x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(p.harga)"></span>
                                        <span
                                            class="text-[9px] bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 px-1.5 py-0.5 rounded-md font-bold"
                                            x-text="p.pertemuan + ' Sesi'"></span>
                                    </div>
                                </div>
                                <div class="flex gap-0.5 shrink-0">
                                    <button @click="editPaket(p)" :disabled="isLoading"
                                        class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/30 rounded-lg transition-colors"><i
                                            class="fas fa-edit text-xs"></i></button>
                                    <button @click="deletePaket(p.id)" :disabled="isLoading"
                                        class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"><i
                                            class="fas text-xs"
                                            :class="isLoading ? 'fa-spinner fa-spin' : 'fa-trash'"></i></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pembayaranHandler', (initialSummaries, initialSiswas, initialPakets, initialDiskons) => ({
                summaries: initialSummaries || [],
                siswas: initialSiswas || [],
                pakets: initialPakets || [],
                diskons: initialDiskons || [],
                filterSearch: '',
                filterBulan: 'all',
                filterStatus: '0',
                showAddModal: false,
                showPaketModal: false,
                showDetailModal: false,
                showDiskonModal: false,
                siswaSearchModal: '',
                hpSearchModal: '',
                isLoading: false,
                activeDetail: {},
                form: {
                    id_siswa: '',
                    harga: '',
                    keterangan: '',
                    status: 0
                },
                paketForm: {
                    id: null,
                    nama_paket: '',
                    harga: '',
                    pertemuan: 3
                },
                diskonForm: {
                    id: null,
                    no_hp: '',
                    diskon: '',
                    keterangan: ''
                },

                refreshToTab() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', 'pembayaran');
                    window.location.href = url.toString();
                },

                get filteredSummaries() {
                    let rawFiltered = this.summaries.filter(item => {
                        const matchesSearch = this.filterSearch === '' ||
                            (item.siswa && item.siswa.name.toLowerCase().includes(this
                                .filterSearch.toLowerCase())) ||
                            (item.no_hp && item.no_hp.includes(this.filterSearch)) ||
                            (item.keterangan && item.keterangan.toLowerCase().includes(this
                                .filterSearch.toLowerCase()));
                        const matchesBulan = this.filterBulan === 'all' || item.bulan ===
                            this.filterBulan;
                        const matchesStatus = this.filterStatus === 'all' || item.status
                            .toString() === this.filterStatus;
                        return matchesSearch && matchesBulan && matchesStatus;
                    });

                    let grouped = {};
                    rawFiltered.forEach(item => {
                        let hpKey = item.no_hp || (item.siswa ? item.siswa.no_hp : 'N/A');
                        if (!hpKey) hpKey = 'N/A';

                        if (!grouped[hpKey]) {
                            grouped[hpKey] = {
                                no_hp: hpKey,
                                siswa_names_arr: [],
                                total_harga: 0,
                                total_sudah_dibayar: 0,
                                gabungan_keterangan: [],
                                raw_items: [],
                                payment_details: [],
                                status: item.status,
                                tanggal_pembayaran: item.tanggal_pembayaran,
                                pembayaran_via: item.pembayaran_via,
                                tanggal_format: item.tanggal_format,
                                id_siswa_trigger: item.id_siswa
                            };
                        }

                        if (item.siswa && !grouped[hpKey].siswa_names_arr.includes(item
                                .siswa.name)) {
                            grouped[hpKey].siswa_names_arr.push(item.siswa.name);
                        }

                        grouped[hpKey].total_harga += parseInt(item.harga || 0);
                        grouped[hpKey].total_sudah_dibayar += parseInt(item
                            .total_sudah_dibayar || 0);
                        grouped[hpKey].gabungan_keterangan.push(item.keterangan);
                        grouped[hpKey].raw_items.push(item);

                        if (item.details && Array.isArray(item.details)) {
                            item.details.forEach(d => {
                                if (!grouped[hpKey].payment_details.some(existing =>
                                        existing.id === d.id)) {
                                    grouped[hpKey].payment_details.push(d);
                                }
                            });
                        }
                    });

                    return Object.values(grouped).map(g => {
                        const diskonObj = this.diskons.find(d => d.no_hp === g.no_hp);
                        const nominalDiskon = diskonObj ? parseInt(diskonObj.diskon || 0) :
                            0;
                        const keteranganDiskon = diskonObj ? diskonObj.keterangan : '';

                        let totalAkhir = g.total_harga - nominalDiskon;
                        if (totalAkhir < 0) totalAkhir = 0;

                        return {
                            ...g,
                            id_diskon: diskonObj ? diskonObj.id : null,
                            siswa_names: g.siswa_names_arr.join(', '),
                            gabungan_keterangan: g.gabungan_keterangan.filter(k => k).join(
                                ', '),
                            nominal_diskon: nominalDiskon,
                            keterangan_diskon: keteranganDiskon,
                            total_akhir: totalAkhir
                        };
                    });
                },

                get filteredSiswasForModal() {
                    if (!this.siswaSearchModal) return [];
                    return this.siswas.filter(s => s.name.toLowerCase().includes(this
                        .siswaSearchModal.toLowerCase()));
                },

                get filteredFamiliesForModal() {
                    let families = {};
                    this.summaries.forEach(item => {
                        let hpKey = item.no_hp || (item.siswa ? item.siswa.no_hp : null);
                        if (hpKey && hpKey !== 'N/A') {
                            if (!families[hpKey]) {
                                families[hpKey] = {
                                    no_hp: hpKey,
                                    names: []
                                };
                            }
                            if (item.siswa && !families[hpKey].names.includes(item.siswa
                                    .name)) {
                                families[hpKey].names.push(item.siswa.name);
                            }
                        }
                    });

                    let mappedFamilies = Object.values(families).map(f => ({
                        no_hp: f.no_hp,
                        siswa_names: f.names.join(', ')
                    }));

                    if (!this.hpSearchModal) return mappedFamilies;
                    return mappedFamilies.filter(f =>
                        f.no_hp.includes(this.hpSearchModal) ||
                        f.siswa_names.toLowerCase().includes(this.hpSearchModal.toLowerCase())
                    );
                },

                getKeluargaLabelByHp(hp) {
                    let families = {};
                    this.summaries.forEach(item => {
                        let hpKey = item.no_hp || (item.siswa ? item.siswa.no_hp : null);
                        if (hpKey === hp && item.siswa) {
                            if (!families[hpKey]) families[hpKey] = [];
                            if (!families[hpKey].includes(item.siswa.name)) families[hpKey]
                                .push(item.siswa.name);
                        }
                    });
                    return families[hp] ? families[hp].join(', ') : 'Anggota tidak terdeteksi';
                },

                openDetailModal(item) {
                    this.activeDetail = item;
                    this.showDetailModal = true;
                },

                openAddPembayaran() {
                    this.form = {
                        id_siswa: '',
                        harga: '',
                        keterangan: '',
                        status: 0
                    };
                    this.siswaSearchModal = '';
                    this.showAddModal = true;
                },

                applyPaket(paketId) {
                    if (!paketId) return;
                    const p = this.pakets.find(x => x.id == paketId);
                    if (p) {
                        this.form.harga = p.harga;
                        this.form.keterangan =
                            `Pembayaran Paket ${p.nama_paket} (${p.pertemuan} Pertemuan)`;
                    }
                },

                async simpanTagihan() {
                    if (!this.form.id_siswa) return Swal.fire('Peringatan', 'Pilih siswa!',
                        'warning');
                    this.isLoading = true;
                    try {
                        const response = await fetch(`{{ route('admin.pembayaran.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menyimpan.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async prosesPenagihanMassal() {
                    const result = await Swal.fire({
                        title: 'Proses Penagihan?',
                        text: "Sistem akan membuat tagihan otomatis sesuai seluruh paket siswa.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#f97316',
                        confirmButtonText: 'Ya, Proses!',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000'
                    });

                    if (result.isConfirmed) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ route('admin.pembayaran.penagihanMassal') }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                async chatWhatsApp(item) {
                    const total = new Intl.NumberFormat('id-ID').format(item.total_akhir);
                    const nama = item.siswa_names;
                    const noHp = item.no_hp;

                    if (!noHp || noHp === 'N/A') return Swal.fire('Error', 'No HP tidak valid',
                        'error');

                    const now = new Date();
                    const bulan = now.toLocaleString('id-ID', {
                        month: 'long',
                        year: 'numeric'
                    }).toUpperCase();
                    const namaBulan = now.toLocaleString('id-ID', {
                        month: 'long'
                    });
                    const tahun = now.getFullYear();

                    let rincianTeks = "";
                    item.raw_items.forEach(d => {
                        rincianTeks +=
                            `* Tagihan : Rp ${new Intl.NumberFormat('id-ID').format(d.harga)} (${d.keterangan || '-'})\n`;
                    });

                    if (item.nominal_diskon > 0) {
                        rincianTeks +=
                            `* Potongan Diskon : - Rp ${new Intl.NumberFormat('id-ID').format(item.nominal_diskon)} (${item.keterangan_diskon})\n`;
                    }

                    const text = `Reminder:\n` +
                        `TAGIHAN BIMBEL "E-LING COURSE"\n\n` +
                        `Anggota Keluarga Siswa : ${nama}\n` +
                        `No HP : ${noHp}\n` +
                        `Periode : ${bulan}\n\n` +
                        `Rincian Tagihan:\n` +
                        `${rincianTeks}\n` +
                        `Total Tagihan Bersih : Rp ${total},-\n\n` +
                        `Pembayaran paling lambat : 10 ${namaBulan} ${tahun}\n\n` +
                        `Silakan konfirmasi jika sudah melakukan pembayaran.\n\n` +
                        `Terima kasih.\n` +
                        `E-Ling Course`;

                    window.open(
                        `https://wa.me/${noHp.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(text)}`,
                        '_blank');
                },

                async prosesBayarSiswa(item) {
                    const sisaTagihan = item.total_akhir - item.total_sudah_dibayar;
                    const {
                        value: formValues
                    } = await Swal.fire({
                        title: '<span class="text-xl font-bold">Catat Setoran</span>',
                        html: `
                            <div class="text-left space-y-3 px-2 pt-2">
                                <p class="text-xs text-gray-500">Sisa kekurangan tagihan saat ini: <strong>Rp ${new Intl.NumberFormat('id-ID').format(sisaTagihan)}</strong></p>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Nominal Rupiah (Setoran)</label>
                                    <input id="swal-nominal" type="number" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white" value="${sisaTagihan > 0 ? sisaTagihan : ''}">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Keterangan Catatan Transaksi</label>
                                    <input id="swal-keterangan" type="text" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white" value="Bayar Angsuran / Cicilan">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Tanggal Pembayaran</label>
                                    <input id="swal-tanggal" type="date" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Metode Via</label>
                                    <select id="swal-via" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        <option value="0">💵 Tunai / Cash</option>
                                        <option value="1">🏦 Transfer Bank</option>
                                    </select>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Simpan Setoran',
                        confirmButtonColor: '#2563eb',
                        background: document.documentElement.classList.contains('dark') ?
                            '#111827' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000',
                        preConfirm: () => {
                            const nom = document.getElementById('swal-nominal').value;
                            if (!nom || parseInt(nom) <= 0) {
                                Swal.showValidationMessage(
                                    'Nominal harus diisi dengan benar!');
                                return false;
                            }
                            return {
                                nominal: nom,
                                keterangan_detail: document.getElementById(
                                    'swal-keterangan').value,
                                tanggal_pembayaran: document.getElementById(
                                    'swal-tanggal').value,
                                pembayaran_via: document.getElementById('swal-via')
                                    .value
                            }
                        }
                    });

                    if (formValues) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ url('admin/pembayaran/bayar-siswa') }}/${item.id_siswa_trigger}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify(formValues)
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal mencatat data setoran.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                async ubahKeLunas(item) {
                    const result = await Swal.fire({
                        title: 'Ubah Ke Lunas?',
                        text: "Status tagihan keluarga ini akan langsung diselesaikan penuh menjadi Lunas.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Ya, Set Lunas!',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000'
                    });

                    if (result.isConfirmed) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ url('admin/pembayaran/ke-lunas-massal') }}/${item.id_siswa_trigger}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses perubahan status.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                openDiskonManagerModal() {
                    this.resetDiskonForm();
                    this.showDiskonModal = true;
                },

                resetDiskonForm() {
                    this.diskonForm = {
                        id: null,
                        no_hp: '',
                        diskon: '',
                        keterangan: ''
                    };
                    this.hpSearchModal = '';
                },

                editDiskon(d) {
                    this.diskonForm = {
                        id: d.id,
                        no_hp: d.no_hp,
                        diskon: d.diskon,
                        keterangan: d.keterangan || ''
                    };
                    this.hpSearchModal = `${d.no_hp} - (${this.getKeluargaLabelByHp(d.no_hp)})`;
                },

                async simpanDiskon() {
                    if (!this.diskonForm.no_hp) return Swal.fire('Peringatan',
                        'Pilih nomor HP keluarga!', 'warning');
                    this.isLoading = true;
                    try {
                        const response = await fetch(`{{ route('admin.diskon.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.diskonForm)
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal memproses diskon.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async hapusDiskon(id) {
                    if (!confirm('Hapus aturan diskon ini?')) return;
                    this.isLoading = true;
                    try {
                        const response = await fetch(`{{ url('admin/diskon') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menghapus diskon.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async lunaskanSemua() {
                    const result = await Swal.fire({
                        title: 'Selesaikan Semua?',
                        text: "Semua sistem tagihan tanpa terkecuali akan langsung dianggap lunas.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'Ya, Lunaskan!',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000'
                    });

                    if (result.isConfirmed) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ route('admin.pembayaran.lunasSemua') }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                exportPdf() {
                    const params = new URLSearchParams({
                        search: this.filterSearch,
                        bulan: this.filterBulan,
                        status: this.filterStatus
                    });
                    window.location.href =
                        `{{ route('admin.pembayaran.export') }}?${params.toString()}`;

                    Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    }).fire({
                        icon: 'success',
                        title: 'Sedang menyiapkan dokumen PDF...'
                    });
                },

                openPaketModal() {
                    this.resetPaketForm();
                    this.showPaketModal = true;
                },

                resetPaketForm() {
                    this.paketForm = {
                        id: null,
                        nama_paket: '',
                        harga: '',
                        pertemuan: 3
                    };
                },

                async savePaket() {
                    this.isLoading = true;
                    const url = this.paketForm.id ?
                        `{{ url('admin/paket') }}/${this.paketForm.id}` :
                        `{{ route('admin.paket.store') }}`;
                    const method = this.paketForm.id ? 'PUT' : 'POST';
                    try {
                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.paketForm)
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menyimpan paket.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                editPaket(p) {
                    this.paketForm = {
                        id: p.id,
                        nama_paket: p.nama_paket,
                        harga: p.harga,
                        pertemuan: p.pertemuan
                    };
                },

                async deletePaket(id) {
                    if (!confirm('Hapus paket ini?')) return;
                    this.isLoading = true;
                    try {
                        const response = await fetch(`{{ url('admin/paket') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menghapus.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                }
            }));
        });
    </script>
@endpush
