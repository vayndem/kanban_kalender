<div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-6 rounded-xl shadow-inner" x-data="siswaHandler({{ $allSiswas->toJson() }}, {{ $allArsips->toJson() }}, {{ $pakets->toJson() }}, {{ $jadwalsData->toJson() }}, {{ $haris->toJson() }}, {{ $sesis->toJson() }})">

    <div class="flex flex-col gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-xl font-bold text-gray-900 sm:text-2xl dark:text-white flex items-center">
                <i class="fas mr-3 text-blue-500"
                    :class="viewMode === 'aktif' ? 'fa-user-graduate' : 'fa-archive'"></i>
                <span x-text="viewMode === 'aktif' ? 'Data Master Siswa' : 'Arsip Data Siswa'"></span>
            </h3>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Total: <span x-text="viewMode === 'aktif' ? allSiswas.length : allArsips.length"></span> Siswa
            </p>
        </div>

        <div class="flex flex-col gap-3 w-full sm:w-auto sm:flex-row sm:items-center">
            <div
                class="flex bg-gray-200 dark:bg-gray-700 p-1 rounded-xl shadow-sm border dark:border-gray-600 w-full sm:w-auto">
                <button @click="viewMode = 'aktif'"
                    :class="{ 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-300': viewMode === 'aktif', 'text-gray-500 dark:text-gray-400': viewMode !== 'aktif' }"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                    AKTIF
                </button>
                <button @click="viewMode = 'arsip'"
                    :class="{ 'bg-white dark:bg-gray-600 shadow-sm text-red-600 dark:text-red-300': viewMode === 'arsip', 'text-gray-500 dark:text-gray-400': viewMode !== 'arsip' }"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                    ARSIP
                </button>
            </div>

            <div class="relative w-full sm:w-64">
                <input type="text" x-model="siswaSearch" placeholder="Cari nama atau kelas..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:border-blue-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>

            <button x-show="viewMode === 'aktif'" @click="openTambah()"
                class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tambah
            </button>
        </div>
    </div>

    <div x-show="viewMode === 'aktif'" x-transition
        class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <p
                class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <i class="fas fa-filter text-blue-500"></i> Filter Siswa
            </p>
            <div class="flex items-center gap-2">
                <span x-show="hasActiveFilter"
                    class="text-[10px] font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 px-2 py-0.5 rounded-full"
                    x-text="filteredSiswa.length + ' siswa ditemukan'"></span>
                <button type="button" x-show="hasActiveFilter" @click="resetFilter()"
                    class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1">
                    <i class="fas fa-times"></i> Reset
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Kelas</label>
                <select x-model="filterKelas"
                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Semua Kelas</option>
                    <template x-for="k in kelasList" :key="k">
                        <option :value="k" x-text="k"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Paket</label>
                <select x-model="filterPaket"
                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Semua Paket</option>
                    <template x-for="p in pakets" :key="p.id">
                        <option :value="p.id" x-text="p.nama_paket"></option>
                    </template>
                </select>
            </div>

            <div x-data="{ openSesi: false }" class="relative">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Sesi</label>
                <button type="button" @click="openSesi = !openSesi"
                    class="w-full text-sm text-left rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 flex items-center justify-between focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span x-text="filterSesis.length ? filterSesis.length + ' sesi dipilih' : 'Semua Sesi'"
                        :class="filterSesis.length ? 'text-blue-600 dark:text-blue-300 font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                        :class="openSesi ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="openSesi" @click.outside="openSesi = false" x-transition
                    class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                    <template x-for="s in allSesis" :key="s.id">
                        <label
                            class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" :value="s.id" x-model="filterSesis"
                                class="rounded text-blue-600 focus:ring-blue-500">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white font-medium"
                                    x-text="s.name || s.nama_sesi"></p>
                                <p class="text-[10px] text-gray-400"
                                    x-text="s.start_time ? s.start_time.substring(0,5) + ' - ' + s.end_time.substring(0,5) : ''">
                                </p>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <div x-data="{ openGuru: false }" class="relative">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Guru</label>
                <button type="button" @click="openGuru = !openGuru"
                    class="w-full text-sm text-left rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 flex items-center justify-between focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span x-text="filterGurus.length ? filterGurus.length + ' guru dipilih' : 'Semua Guru'"
                        :class="filterGurus.length ? 'text-blue-600 dark:text-blue-300 font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                        :class="openGuru ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="openGuru" @click.outside="openGuru = false" x-transition
                    class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                    <template x-for="g in guruList" :key="g.id">
                        <label
                            class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" :value="g.id" x-model="filterGurus"
                                class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-900 dark:text-white" x-text="g.name"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-gray-100 dark:border-gray-700">
            <button type="button" @click="exportPdf()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                <i class="fas fa-file-pdf"></i> Export PDF <span x-show="hasActiveFilter"
                    class="text-[10px] bg-red-500 px-1.5 py-0.5 rounded"
                    x-text="'(' + filteredSiswa.length + ')'"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
        <template x-for="siswa in filteredSiswa" :key="siswa.id">
            <div x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col justify-between h-full"
                :class="{ 'opacity-75 grayscale-[0.5]': viewMode === 'arsip' }">
                <div>
                    <div class="h-1 w-full transition-colors duration-500"
                        :class="getStatusJadwal(siswa).isKurang ? 'bg-orange-500 animate-pulse' : 'bg-blue-500'"></div>
                    <div class="p-4 sm:p-5">
                        <div class="flex items-start justify-between mb-4 gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-white shadow-inner shrink-0 transition-transform group-hover:scale-105 duration-300"
                                    :class="getStatusJadwal(siswa).isKurang ? 'bg-gradient-to-br from-orange-400 to-red-500' :
                                        'bg-gradient-to-br from-blue-500 to-indigo-600'">
                                    <span class="text-base sm:text-lg font-bold" x-text="siswa.name.charAt(0)"></span>
                                </div>
                                <div class="overflow-hidden min-w-0 flex-1">
                                    <h4 class="font-bold truncate text-sm sm:text-base transition-colors duration-300 leading-snug"
                                        :class="getStatusJadwal(siswa).isKurang ? 'text-orange-500' :
                                            'text-gray-900 dark:text-white'"
                                        x-text="siswa.name"></h4>
                                    <p
                                        class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1 uppercase tracking-wider font-semibold mt-0.5">
                                        <i class="fas fa-id-badge opacity-50"></i>
                                        <span x-text="siswa.kelas || 'N/A'"></span>
                                    </p>
                                </div>
                            </div>
                            <template x-if="siswa.paket_pembayaran">
                                <span
                                    class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md border shrink-0 max-w-[80px] truncate"
                                    :class="getStatusJadwal(siswa).isKurang ?
                                        'bg-orange-50 dark:bg-orange-900/20 text-orange-600 border-orange-200' :
                                        'bg-blue-50 dark:bg-blue-900/20 text-blue-600 border-blue-100'">
                                    <span x-text="getPaketName(siswa.paket_pembayaran)"></span>
                                </span>
                            </template>
                        </div>
                        <div class="flex items-center gap-2 mb-4 text-gray-600 dark:text-gray-400 min-w-0">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center shrink-0">
                                <i class="fas fa-phone-alt text-[10px]"></i>
                            </div>
                            <span class="text-xs font-medium truncate" x-text="siswa.no_hp || '-'"></span>
                        </div>
                    </div>
                </div>
                <div>
                    <div
                        class="flex items-center justify-between p-4 sm:p-5 pt-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <div class="w-2 h-2 rounded-full"
                                :class="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'bg-orange-500' :
                                    'bg-green-500') : 'bg-gray-400'">
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400"
                                x-text="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'Incomplete' : 'Active') : 'Archived'"></span>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <template x-if="viewMode === 'aktif'">
                                <div class="flex gap-1">
                                    <button type="button" @click.stop="openEdit(siswa)"
                                        class="p-1.5 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" @click.stop="hapusSiswa(siswa.id)"
                                        class="p-1.5 text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-box-archive"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="viewMode === 'arsip'">
                                <div class="flex gap-1">
                                    <button type="button" @click.stop="restoreSiswa(siswa.id)"
                                        class="p-1.5 text-green-500 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-rotate-left"></i>
                                    </button>
                                    <button type="button" @click.stop="hapusPermanen(siswa.id)"
                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <template x-if="viewMode === 'aktif'">
                        <div class="text-[8px] text-white font-black text-center py-1 uppercase tracking-widest transition-colors duration-500"
                            :class="getStatusJadwal(siswa).isKurang ? 'bg-orange-500/90' : 'bg-blue-500/90'">
                            <span
                                x-text="getStatusJadwal(siswa).kuota > 0 ? getStatusJadwal(siswa).total + ' dari ' + getStatusJadwal(siswa).kuota + ' Pertemuan' : 'Jadwal Belum Diatur'"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div x-show="showSiswaModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        style="display: none;" x-transition>
        <div @click="showSiswaModal = false" class="absolute inset-0"></div>

        <form @submit.prevent="simpanSiswa"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full overflow-hidden relative border dark:border-gray-700 transition-all duration-300 max-h-[90vh] flex flex-col"
            :class="{ 'max-w-3xl': siswaForm.id, 'max-w-md': !siswaForm.id }" @click.stop>

            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900 shrink-0">
                <h3 class="font-bold text-gray-900 dark:text-white text-base sm:text-lg"
                    x-text="siswaForm.id ? 'Edit Data Siswa' : 'Tambah Siswa Baru'"></h3>
                <button type="button" @click="showSiswaModal = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>

            <div class="overflow-y-auto flex-1">
                <div class="grid grid-cols-1" :class="{ 'md:grid-cols-2': siswaForm.id }">

                    <div class="p-4 sm:p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="col-span-1 sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama
                                    Lengkap</label>
                                <input type="text" x-model="siswaForm.name" required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Panggilan</label>
                                <input type="text" x-model="siswaForm.panggilan"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</label>
                                <input type="text" x-model="siswaForm.kelas"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor
                                HP</label>
                            <input type="text" x-model="siswaForm.no_hp" @input="formatPhone"
                                placeholder="+62812..."
                                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Paket
                                Pembayaran</label>
                            <select x-model="siswaForm.paket_pembayaran"
                                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="">-- Pilih Paket --</option>
                                <template x-for="paket in pakets" :key="paket.id">
                                    <option :value="paket.id" x-text="paket.nama_paket"></option>
                                </template>
                            </select>
                        </div>
                        <div
                            class="pt-4 flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 md:hidden">
                            <button type="button" @click="showSiswaModal = false"
                                class="px-4 py-2 text-sm border rounded-lg dark:text-white border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Batal</button>
                            <button type="submit"
                                class="px-6 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-sm">Simpan</button>
                        </div>
                    </div>

                    <template x-if="siswaForm.id">
                        <div
                            class="p-4 sm:p-6 bg-gray-50/50 dark:bg-gray-800/40 space-y-4 border-t md:border-t-0 md:border-l border-gray-100 dark:border-gray-700">
                            <h4
                                class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-blue-500"></i> Jadwal Kelas Diikuti
                            </h4>
                            <div class="space-y-3 max-h-[300px] md:max-h-[400px] overflow-y-auto pr-1">
                                <template x-for="j in getSiswaJadwalList(siswaForm.id)" :key="j.id">
                                    <div
                                        class="p-3 bg-white dark:bg-gray-700 rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm flex items-start gap-3">
                                        <div
                                            class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400 shrink-0">
                                            <i class="fas fa-clock text-sm"></i>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                                x-text="j.mapel_name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                                                <span class="capitalize" x-text="j.hari_name"></span> | <span
                                                    x-text="j.sesi_name"></span> (<span x-text="j.sesi_time"></span>)
                                            </p>
                                            <p
                                                class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                                <i class="fas fa-chalkboard-user opacity-60"></i> <span
                                                    x-text="j.guru_name"></span>
                                                <span class="mx-1">•</span>
                                                <i class="fas fa-door-open opacity-60"></i> <span
                                                    x-text="j.ruang_name"></span>
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="getSiswaJadwalList(siswaForm.id).length === 0">
                                    <div
                                        class="text-center py-8 border border-dashed border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700/30">
                                        <i
                                            class="fas fa-calendar-times text-gray-300 dark:text-gray-600 text-2xl mb-2"></i>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Belum ada jadwal yang
                                            diatur untuk siswa ini.</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div
                class="p-4 border-t dark:border-gray-700 justify-end gap-2 bg-gray-50 dark:bg-gray-900 shrink-0 hidden md:flex">
                <button type="button" @click="showSiswaModal = false"
                    class="px-4 py-2 text-sm border rounded-lg dark:text-white border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Batal</button>
                <button type="submit"
                    class="px-6 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('siswaHandler', (initialSiswa, initialArsip, paketData, jadwalData, hariData, sesiData) =>
                ({
                    allSiswas: initialSiswa || [],
                    allArsips: initialArsip || [],
                    pakets: paketData || [],
                    allJadwals: jadwalData || [],
                    allHaris: hariData || [],
                    allSesis: sesiData || [],
                    viewMode: 'aktif',
                    showSiswaModal: false,
                    siswaSearch: '',
                    siswaForm: {
                        id: null,
                        name: '',
                        panggilan: '',
                        kelas: '',
                        no_hp: '',
                        paket_pembayaran: ''
                    },
                    filterKelas: '',
                    filterPaket: '',
                    filterSesis: [],
                    filterGurus: [],

                    get kelasList() {
                        return [...new Set(
                            this.allSiswas
                            .map(s => s.kelas)
                            .filter(Boolean)
                        )].sort();
                    },

                    get guruList() {
                        const seen = new Set();
                        return this.allJadwals
                            .filter(j => j.guru)
                            .filter(j => {
                                if (seen.has(j.guru.id)) return false;
                                seen.add(j.guru.id);
                                return true;
                            })
                            .map(j => j.guru)
                            .sort((a, b) => a.name.localeCompare(b.name));
                    },

                    get hasActiveFilter() {
                        return this.filterKelas || this.filterPaket ||
                            this.filterSesis.length > 0 || this.filterGurus.length > 0 ||
                            this.siswaSearch;
                    },

                    get filteredSiswa() {
                        let data = this.viewMode === 'aktif' ? this.allSiswas : this.allArsips;

                        if (this.siswaSearch) {
                            const search = this.siswaSearch.toLowerCase();
                            data = data.filter(s =>
                                (s.name && s.name.toLowerCase().includes(search)) ||
                                (s.kelas && s.kelas.toLowerCase().includes(search))
                            );
                        }

                        if (this.viewMode === 'aktif') {
                            if (this.filterKelas) {
                                data = data.filter(s => s.kelas === this.filterKelas);
                            }

                            if (this.filterPaket) {
                                data = data.filter(s => Number(s.paket_pembayaran) === Number(this
                                    .filterPaket));
                            }

                            if (this.filterSesis.length > 0) {
                                const sesiIds = this.filterSesis.map(Number);
                                const siswaIdsDenganSesi = new Set(
                                    this.allJadwals
                                    .filter(j => sesiIds.includes(Number(j.sesi_id)))
                                    .map(j => Number(j.siswa_id))
                                );
                                data = data.filter(s => siswaIdsDenganSesi.has(Number(s.id)));
                            }

                            if (this.filterGurus.length > 0) {
                                const guruIds = this.filterGurus.map(Number);
                                const siswaIdsDenganGuru = new Set(
                                    this.allJadwals
                                    .filter(j => j.guru && guruIds.includes(Number(j.guru_id || j
                                        .guru?.id)))
                                    .map(j => Number(j.siswa_id))
                                );
                                data = data.filter(s => siswaIdsDenganGuru.has(Number(s.id)));
                            }
                        }

                        return data.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                    },

                    resetFilter() {
                        this.filterKelas = '';
                        this.filterPaket = '';
                        this.filterSesis = [];
                        this.filterGurus = [];
                        this.siswaSearch = '';
                    },

                    getPaketName(id) {
                        const p = this.pakets.find(x => x.id == id);
                        return p ? p.nama_paket : 'N/A';
                    },

                    getStatusJadwal(siswa) {
                        const totalJadwal = this.allJadwals.filter(j => Number(j.siswa_id) === Number(siswa
                            .id)).length;
                        const paket = this.pakets.find(p => p.id == siswa.paket_pembayaran);
                        const kuota = paket ? paket.pertemuan : 0;

                        return {
                            total: totalJadwal,
                            kuota: kuota,
                            isKurang: totalJadwal < kuota,
                            isComplete: totalJadwal >= kuota && kuota > 0
                        };
                    },

                    getSiswaJadwalList(siswaId) {
                        if (!siswaId) return [];
                        return this.allJadwals
                            .filter(j => Number(j.siswa_id) === Number(siswaId))
                            .map(j => {
                                const mapelObj = j.mata_pelajaran || j.mataPelajaran;
                                const hariObj = j.hari || this.allHaris.find(h => Number(h.id) ===
                                    Number(j.hari_id));
                                const sesiObj = j.sesi || this.allSesis.find(s => Number(s.id) ===
                                    Number(j.sesi_id));

                                let startT = '';
                                let endT = '';
                                let sName = `Sesi ${j.sesi_id}`;

                                if (sesiObj) {
                                    sName = sesiObj.name || sesiObj.nama_sesi || sName;
                                    if (sesiObj.start_time) startT = sesiObj.start_time.substring(0, 5);
                                    if (sesiObj.end_time) endT = sesiObj.end_time.substring(0, 5);
                                }

                                let hName = 'N/A';
                                if (hariObj) {
                                    hName = hariObj.name || hariObj.nama || hName;
                                }

                                return {
                                    id: j.id,
                                    mapel_name: mapelObj ? mapelObj.name : 'N/A',
                                    guru_name: j.guru ? j.guru.name : 'N/A',
                                    ruang_name: j.ruang ? j.ruang.name : 'N/A',
                                    hari_name: hName,
                                    sesi_name: sName,
                                    sesi_time: (startT && endT) ? `${startT} - ${endT}` : ''
                                };
                            });
                    },

                    formatPhone() {
                        let val = this.siswaForm.no_hp;
                        if (!val) return;

                        let digits = val.replace(/\D/g, '');

                        if (digits.startsWith('0')) {
                            digits = '62' + digits.substring(1);
                        }

                        if (digits.startsWith('8')) {
                            digits = '62' + digits;
                        }

                        this.siswaForm.no_hp = '+' + digits;
                    },

                    openTambah() {
                        this.siswaForm = {
                            id: null,
                            name: '',
                            panggilan: '',
                            kelas: '',
                            no_hp: '',
                            paket_pembayaran: ''
                        };
                        this.showSiswaModal = true;
                    },

                    openEdit(siswa) {
                        this.siswaForm = {
                            id: siswa.id,
                            name: siswa.name || '',
                            panggilan: siswa.panggilan || '',
                            kelas: siswa.kelas || '',
                            no_hp: siswa.no_hp || '',
                            paket_pembayaran: siswa.paket_pembayaran || ''
                        };
                        this.showSiswaModal = true;
                    },

                    async simpanSiswa() {
                        const isEdit = !!this.siswaForm.id;
                        const url = isEdit ? `{{ url('admin/siswa') }}/${this.siswaForm.id}` :
                            `{{ route('admin.siswa.store') }}`;

                        const payload = {
                            ...this.siswaForm,
                            _token: '{{ csrf_token() }}'
                        };
                        if (isEdit) payload._method = 'PUT';

                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(payload)
                            });
                            const res = await response.json();
                            if (res.status === 'success') {
                                window.location.reload();
                            } else {
                                alert(res.message);
                            }
                        } catch (e) {
                            alert('Sistem Error');
                        }
                    },

                    async hapusSiswa(id) {
                        if (!confirm('Pindahkan ke arsip?')) return;
                        try {
                            const response = await fetch(`{{ url('admin/siswa') }}/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });
                            const res = await response.json();
                            if (res.status === 'success') window.location.reload();
                        } catch (e) {
                            alert('Gagal mengarsipkan');
                        }
                    },

                    async restoreSiswa(id) {
                        if (!confirm('Kembalikan ke daftar aktif?')) return;
                        try {
                            const response = await fetch(`{{ url('admin/arsip') }}/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT'
                                })
                            });
                            const res = await response.json();
                            if (res.status === 'success') window.location.reload();
                        } catch (e) {
                            alert('Gagal memulihkan');
                        }
                    },

                    async hapusPermanen(id) {
                        if (!confirm('Hapus permanen dari arsip?')) return;
                        try {
                            const response = await fetch(`{{ url('admin/arsip') }}/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });
                            const res = await response.json();
                            if (res.status === 'success') window.location.reload();
                        } catch (e) {
                            alert('Gagal menghapus');
                        }
                    },

                    exportPdf() {
                        const params = new URLSearchParams();

                        if (this.filterKelas) params.set('kelas', this.filterKelas);
                        if (this.filterPaket) params.set('paket_id', this.filterPaket);
                        if (this.filterSesis.length) params.set('sesi_ids', this.filterSesis.join(','));
                        if (this.filterGurus.length) params.set('guru_ids', this.filterGurus.join(','));
                        if (this.siswaSearch) params.set('search', this.siswaSearch);

                        window.location.href = `/admin/siswa/export-pdf?${params.toString()}`;
                    }
                }));
        });
    </script>
@endpush
