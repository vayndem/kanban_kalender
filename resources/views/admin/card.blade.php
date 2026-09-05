<div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-6 rounded-xl shadow-inner" x-data="siswaHandler({
    initialSiswa: @js($allSiswas),
    initialArsip: @js($allArsips),
    paketData: @js($pakets),
    scheduleMetaData: @js($studentScheduleMeta),
    hariData: @js($haris),
    sesiData: @js($sesis),
    guruData: @js($allGurus),
    ruangData: @js($allRuangs),
    routes: {
        siswaBase: @js(url('admin/siswa')),
        arsipBase: @js(url('admin/arsip')),
        tandaBase: @js(url('admin/tanda')),
        tandaStore: @js(route('admin.tanda.store')),
    },
})">

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
                <button @click="viewMode = 'aktif'; selectedSiswas = []"
                    :class="{ 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-300': viewMode === 'aktif', 'text-gray-500 dark:text-gray-400': viewMode !== 'aktif' }"
                    class="flex-1 sm:flex-none px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                    AKTIF
                </button>
                <button @click="viewMode = 'arsip'; selectedSiswas = []"
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

            <div class="flex gap-2 w-full sm:w-auto">
                <button x-show="viewMode === 'aktif' && selectedSiswas.length > 0"
                    @click="hapusSiswa(selectedSiswas.join(','))"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors shadow-sm animate-fade-in">
                    <i class="fas fa-box-archive mr-2"></i> Arsipkan Terpilih (<span
                        x-text="selectedSiswas.length"></span>)
                </button>
                <a href="{{ route('admin.workshop.index') }}" x-show="viewMode === 'aktif'"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-plus mr-2"></i> Tambah di Workshop
                </a>
            </div>
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
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

            <div x-data="{ openSesi: false, searchSesi: '' }" class="relative">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Sesi</label>
                <button type="button" @click="openSesi = !openSesi"
                    class="w-full text-sm text-left rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 flex items-center justify-between focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span x-text="filterSesis.length ? filterSesis.length + ' sesi dipilih' : 'Semua Sesi'"
                        :class="filterSesis.length ? 'text-blue-600 dark:text-blue-300 font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                        :class="openSesi ? 'rotate-180' : ''"></i>
                </button>
                <template x-if="openSesi">
                    <div @click.outside="openSesi = false"
                        class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                        <div
                            class="sticky top-0 z-10 border-b border-gray-100 bg-white p-2 dark:border-gray-700 dark:bg-gray-800">
                            <input type="search" x-model="searchSesi" placeholder="Cari sesi atau jam..."
                                class="w-full rounded-lg border px-3 py-2 text-xs">
                        </div>
                        <template
                            x-for="s in allSesis.filter(item => ((item.name || item.nama_sesi || '') + ' ' + (item.start_time || '') + ' ' + (item.end_time || '')).toLowerCase().includes(searchSesi.toLowerCase()))"
                            :key="s.id">
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
                </template>
            </div>

            <div x-data="{ openGuru: false, searchGuru: '' }" class="relative">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Guru</label>
                <button type="button" @click="openGuru = !openGuru"
                    class="w-full text-sm text-left rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 flex items-center justify-between focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span x-text="filterGurus.length ? filterGurus.length + ' guru dipilih' : 'Semua Guru'"
                        :class="filterGurus.length ? 'text-blue-600 dark:text-blue-300 font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                        :class="openGuru ? 'rotate-180' : ''"></i>
                </button>
                <template x-if="openGuru">
                    <div @click.outside="openGuru = false"
                        class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                        <div
                            class="sticky top-0 z-10 border-b border-gray-100 bg-white p-2 dark:border-gray-700 dark:bg-gray-800">
                            <input type="search" x-model="searchGuru" placeholder="Cari nama guru..."
                                class="w-full rounded-lg border px-3 py-2 text-xs">
                        </div>
                        <template
                            x-for="g in guruList.filter(item => item.name.toLowerCase().includes(searchGuru.toLowerCase()))"
                            :key="g.id">
                            <label
                                class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <input type="checkbox" :value="g.id" x-model="filterGurus"
                                    class="rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-900 dark:text-white" x-text="g.name"></span>
                            </label>
                        </template>
                    </div>
                </template>
            </div>

            <div x-data="{ openRuang: false, searchRuang: '' }" class="relative">
                <label
                    class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Ruang</label>
                <button type="button" @click="openRuang = !openRuang"
                    class="w-full text-sm text-left rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-2 flex items-center justify-between focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span x-text="filterRuangs.length ? filterRuangs.length + ' ruang dipilih' : 'Semua Ruang'"
                        :class="filterRuangs.length ? 'text-blue-600 dark:text-blue-300 font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                        :class="openRuang ? 'rotate-180' : ''"></i>
                </button>
                <template x-if="openRuang">
                    <div @click.outside="openRuang = false"
                        class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                        <div
                            class="sticky top-0 z-10 border-b border-gray-100 bg-white p-2 dark:border-gray-700 dark:bg-gray-800">
                            <input type="search" x-model="searchRuang" placeholder="Cari ruang..."
                                class="w-full rounded-lg border px-3 py-2 text-xs">
                        </div>
                        <template
                            x-for="r in ruangList.filter(item => item.name.toLowerCase().includes(searchRuang.toLowerCase()))"
                            :key="r.id">
                            <label
                                class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <input type="checkbox" :value="r.id" x-model="filterRuangs"
                                    class="rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-900 dark:text-white" x-text="r.name"></span>
                            </label>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700">
            <label
                class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                <input type="checkbox" @change="toggleSelectAll($el.checked)" :checked="isAllSelected()"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 w-4 h-4">
                Pilih Semua yang Tampil
            </label>
            <button type="button" @click="exportExcel()" class="btn-export text-sm">
                <i class="fas fa-file-excel"></i> Export Excel <span x-show="hasActiveFilter"
                    class="text-[10px] bg-red-500 px-1.5 py-0.5 rounded"
                    x-text="'(' + filteredSiswa.length + ')'"></span>
            </button>
        </div>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 select-none">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" @change="toggleSelectAll($el.checked)" :checked="isAllSelected()"
                                class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 w-4 h-4 shadow-sm cursor-pointer">
                        </th>
                        <th class="p-4 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            @click="toggleSort('name')">
                            Siswa <i class="fas ml-1 text-[10px]"
                                :class="sortField === 'name' ? (sortOrder === 'asc' ? 'fa-sort-up' :
                                    'fa-sort-down') : 'fa-sort text-gray-300'"></i>
                        </th>
                        <th class="p-4 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            @click="toggleSort('kelas')">
                            Kelas & Paket <i class="fas ml-1 text-[10px]"
                                :class="sortField === 'kelas' ? (sortOrder === 'asc' ? 'fa-sort-up' :
                                    'fa-sort-down') : 'fa-sort text-gray-300'"></i>
                        </th>
                        <th class="p-4">Kontak</th>
                        <th class="p-4">Status & Kuota Pertemuan</th>
                        <th class="p-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                    <template x-for="siswa in filteredSiswa" :key="siswa.id">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors group"
                            :class="{
                                'opacity-75 grayscale-[0.5]': viewMode === 'arsip',
                                'bg-blue-50/30 dark:bg-blue-900/10': selectedSiswas
                                    .includes(siswa.id)
                            }">
                            <td class="p-4 text-center">
                                <input type="checkbox" :value="siswa.id" x-model="selectedSiswas"
                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 w-4 h-4 shadow-sm cursor-pointer transition-transform group-hover:scale-105">
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shadow-inner shrink-0 text-sm font-bold"
                                        :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                            'bg-gradient-to-br from-orange-400 to-red-500' :
                                            'bg-gradient-to-br from-blue-50 to-indigo-600'">
                                        <span x-text="siswa.name.charAt(0)"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 dark:text-white truncate"
                                            :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                                'text-orange-500' : ''"
                                            x-text="siswa.name"></p>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5"
                                            x-text="'ID: #' + siswa.id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="space-y-1">
                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                                        <i class="fas fa-id-badge opacity-60 text-[10px]"></i> <span
                                            x-text="siswa.kelas || 'N/A'"></span>
                                    </span>
                                    <template x-if="siswa.paket_pembayaran">
                                        <div class="text-[10px] font-bold uppercase tracking-wide truncate max-w-[150px]"
                                            :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                                'text-orange-500' : 'text-blue-500'">
                                            <span x-text="getPaketName(siswa.paket_pembayaran)"></span>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-4 text-gray-600 dark:text-gray-400 font-medium">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-phone-alt text-[10px] text-gray-400"></i>
                                    <span x-text="siswa.no_hp || '-'"></span>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col gap-1.5 max-w-xs">
                                    <div class="flex items-center justify-between text-xs font-semibold">
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-2 h-2 rounded-full"
                                                :class="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ?
                                                    'bg-orange-500 animate-pulse' : 'bg-green-500') : 'bg-gray-400'">
                                            </div>
                                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400"
                                                x-text="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'Incomplete' : 'Active') : 'Archived'"></span>
                                        </div>
                                        <span class="text-gray-500 dark:text-gray-400 text-[10px]"
                                            x-text="getStatusJadwal(siswa).kuota > 0 ? getStatusJadwal(siswa).total + ' / ' + getStatusJadwal(siswa).kuota + ' Pertemuan' : 'Jadwal Belum Diatur'"></span>
                                    </div>
                                    <template x-if="getStatusJadwal(siswa).kuota > 0">
                                        <div
                                            class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="getStatusJadwal(siswa).isKurang ? 'bg-orange-500' : 'bg-green-500'"
                                                :style="`width: ${Math.min((getStatusJadwal(siswa).total / getStatusJadwal(siswa).kuota) * 100, 100)}%`">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-1">
                                    <template x-if="viewMode === 'aktif'">
                                        <div class="flex gap-1">
                                            <button type="button" @click.stop="openDetail(siswa)"
                                                class="icon-action-primary relative" title="Detail & Catatan">
                                                <i class="fas fa-circle-info"></i>
                                                <span x-show="siswa.tandas && siswa.tandas.length > 0"
                                                    class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-400 border border-white dark:border-gray-800"></span>
                                            </button>
                                            <button type="button" @click.stop="hapusSiswa(siswa.id)"
                                                class="icon-action-warning" title="Arsipkan">
                                                <i class="fas fa-box-archive"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="viewMode === 'arsip'">
                                        <div class="flex gap-1">
                                            <button type="button" @click.stop="restoreSiswa(siswa.id)"
                                                class="icon-action-success" title="Pulihkan">
                                                <i class="fas fa-rotate-left"></i>
                                            </button>
                                            <button type="button" @click.stop="hapusPermanen(siswa.id)"
                                                class="icon-action-danger" title="Hapus Permanen">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredSiswa.length === 0">
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                <i class="fas fa-user-slash text-3xl mb-2 block"></i>
                                Tidak ada data siswa yang ditemukan.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <template x-if="showDetailModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            x-transition>
            <div @click="showDetailModal = false" class="absolute inset-0"></div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden relative border dark:border-gray-700 transition-all duration-300 max-h-[90vh] flex flex-col"
                @click.stop>
                <div
                    class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900 shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base sm:text-lg"
                            x-text="detailSiswa.name"></h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">Profil siswa — data pokok diubah lewat
                            Workshop.</p>
                    </div>
                    <button type="button" @click="showDetailModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="p-4 sm:p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Panggilan
                                    </p>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100"
                                        x-text="detailSiswa.panggilan || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kelas</p>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100"
                                        x-text="detailSiswa.kelas || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nomor HP
                                    </p>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100"
                                        x-text="detailSiswa.no_hp || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Paket</p>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100"
                                        x-text="getPaketName(detailSiswa.paket_pembayaran)"></p>
                                </div>
                            </div>
                            <a :href="`{{ route('admin.workshop.index') }}?edit_siswa=${detailSiswa.id}`"
                                class="btn-neutral text-xs w-full justify-center">
                                <i class="fas fa-pen-to-square"></i> Ubah Data Pokok di Workshop
                            </a>

                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                <h4
                                    class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2 mb-3">
                                    <i class="fas fa-note-sticky text-amber-500"></i> Catatan
                                </h4>
                                <form @submit.prevent="simpanCatatan" class="flex gap-2 mb-3">
                                    <input type="text" x-model="catatanForm.keterangan" required
                                        placeholder="Tulis catatan baru..."
                                        class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <button type="submit" class="btn-primary text-xs shrink-0"
                                        :disabled="isSavingCatatan">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <template x-for="t in detailSiswa.tandas || []" :key="t.id">
                                        <div
                                            class="p-2.5 bg-amber-50/60 dark:bg-amber-950/20 rounded-lg border border-amber-100 dark:border-amber-900/30 flex items-start justify-between gap-2 text-xs">
                                            <span class="text-gray-700 dark:text-gray-200"
                                                x-text="t.keterangan"></span>
                                            <button type="button" @click="hapusCatatan(t.id)" :disabled="isSavingCatatan"
                                                class="text-red-400 hover:text-red-600 shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!detailSiswa.tandas || detailSiswa.tandas.length === 0">
                                        <p class="text-xs italic text-gray-400 py-2">Belum ada catatan untuk siswa ini.
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div
                            class="p-4 sm:p-6 bg-gray-50/50 dark:bg-gray-800/40 space-y-4 border-t md:border-t-0 md:border-l border-gray-100 dark:border-gray-700">
                            <h4
                                class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-blue-500"></i> Jadwal Kelas Diikuti
                            </h4>
                            <div class="space-y-3 max-h-[300px] md:max-h-[400px] overflow-y-auto pr-1">
                                <template x-if="isLoadingJadwal">
                                    <div class="flex items-center justify-center gap-2 py-8 text-sm text-gray-400">
                                        <i class="fas fa-spinner fa-spin text-blue-500"></i>
                                        <span>Memuat jadwal...</span>
                                    </div>
                                </template>
                                <template x-for="j in getSiswaJadwalList(detailSiswa.id)" :key="j.id">
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
                                <template x-if="!isLoadingJadwal && getSiswaJadwalList(detailSiswa.id).length === 0">
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
                    </div>
                </div>

                <div
                    class="p-4 border-t dark:border-gray-700 justify-end gap-2 bg-gray-50 dark:bg-gray-900 shrink-0 flex">
                    <button type="button" @click="showDetailModal = false"
                        class="btn-neutral text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
