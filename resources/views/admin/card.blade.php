<div class="app-card app-card-pad" x-data="siswaHandler({
    initialSiswa: @js($allSiswas),
    initialArsip: @js($allArsips),
    paketData: @js($pakets),
    kemampuanData: @js($kemampuans),
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

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-box bg-primary/10 text-lg text-primary">
                <i class="fas" :class="viewMode === 'aktif' ? 'fa-user-graduate' : 'fa-archive'"></i>
            </span>
            <div>
                <h3 class="text-xl font-black tracking-tight text-base-content sm:text-2xl">
                    <span x-text="viewMode === 'aktif' ? 'Data Master Siswa' : 'Arsip Data Siswa'"></span>
                </h3>
                <p class="mt-0.5 text-xs text-base-content/60 sm:text-sm">
                    Total <span class="font-black text-base-content"
                        x-text="viewMode === 'aktif' ? allSiswas.length : allArsips.length"></span> siswa
                </p>
            </div>
        </div>

        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
            <div class="join w-full sm:w-auto">
                <button @click="viewMode = 'aktif'; selectedSiswas = []"
                    :class="viewMode === 'aktif' ? 'btn-primary' : 'btn-ghost border border-base-300'"
                    class="btn join-item btn-sm flex-1 sm:flex-none">
                    AKTIF
                </button>
                <button @click="viewMode = 'arsip'; selectedSiswas = []"
                    :class="viewMode === 'arsip' ? 'btn-error text-white' : 'btn-ghost border border-base-300'"
                    class="btn join-item btn-sm flex-1 sm:flex-none">
                    ARSIP
                </button>
            </div>

            <label class="relative w-full sm:w-64">
                <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/60"></i>
                <input type="search" x-model="siswaSearch" placeholder="Cari nama atau kelas..."
                    class="app-input pl-10">
            </label>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <button x-show="viewMode === 'aktif' && selectedSiswas.length > 0"
                    @click="hapusSiswa(selectedSiswas.join(','))"
                    class="btn btn-warning btn-sm w-full sm:w-auto">
                    <i class="fas fa-box-archive"></i> Arsipkan Terpilih (<span x-text="selectedSiswas.length"></span>)
                </button>
                <a href="{{ route('admin.workshop.index') }}" x-show="viewMode === 'aktif'"
                    class="btn btn-primary btn-sm w-full sm:w-auto">
                    <i class="fas fa-plus"></i> Tambah di Workshop
                </a>
            </div>
        </div>
    </div>

    <div x-show="viewMode === 'aktif'" x-transition
        class="mb-6 space-y-4 rounded-box border border-base-300 bg-base-200/50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-base-content/60">
                <i class="fas fa-filter text-primary"></i> Filter Siswa
            </p>
            <div class="flex items-center gap-2">
                <span x-show="hasActiveFilter" class="badge badge-primary badge-sm font-bold"
                    x-text="filteredSiswa.length + ' siswa ditemukan'"></span>
                <button type="button" x-show="hasActiveFilter" @click="resetFilter()"
                    class="btn btn-ghost btn-xs text-error hover:bg-error/10">
                    <i class="fas fa-times"></i> Reset
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <div>
                <label class="app-label">Kelas</label>
                <select x-model="filterKelas"
                    class="select select-sm w-full">
                    <option value="">Semua Kelas</option>
                    <template x-for="k in kelasList" :key="k">
                        <option :value="k" x-text="k"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="app-label">Paket</label>
                <select x-model="filterPaket"
                    class="select select-sm w-full">
                    <option value="">Semua Paket</option>
                    <template x-for="p in pakets" :key="p.id">
                        <option :value="p.id" x-text="p.nama_paket"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="app-label">Kemampuan</label>
                <select x-model="filterKemampuan"
                    class="select select-sm w-full">
                    <option value="">Semua Kemampuan</option>
                    <template x-for="k in kemampuans" :key="k.id">
                        <option :value="k.id" x-text="'Level ' + k.level + ' — ' + k.keterangan"></option>
                    </template>
                </select>
            </div>

            <div x-data="{ openSesi: false, searchSesi: '' }" class="relative">
                <label class="app-label">Sesi</label>
                <button type="button" @click="openSesi = !openSesi"
                    class="searchable-select-trigger">
                    <span x-text="filterSesis.length ? filterSesis.length + ' sesi dipilih' : 'Semua Sesi'"
                        :class="filterSesis.length ? 'text-primary font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-base-content/60 transition-transform duration-200"
                        :class="openSesi ? 'rotate-180' : ''"></i>
                </button>
                <template x-if="openSesi">
                    <div @click.outside="openSesi = false"
                        class="searchable-select-panel max-h-48 overflow-y-auto">
                        <div
                            class="searchable-select-search-wrap sticky top-0 z-10">
                            <input type="search" x-model="searchSesi" placeholder="Cari sesi atau jam..."
                                class="input input-xs w-full">
                        </div>
                        <template
                            x-for="s in allSesis.filter(item => ((item.name || item.nama_sesi || '') + ' ' + (item.start_time || '') + ' ' + (item.end_time || '')).toLowerCase().includes(searchSesi.toLowerCase()))"
                            :key="s.id">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-field px-3 py-2 transition hover:bg-primary/10">
                                <input type="checkbox" :value="s.id" x-model="filterSesis"
                                    class="checkbox checkbox-primary">
                                <div class="min-w-0">
                                    <p class="text-sm text-base-content font-medium"
                                        x-text="s.name || s.nama_sesi"></p>
                                    <p class="text-[11px] text-base-content/60"
                                        x-text="s.start_time ? s.start_time.substring(0,5) + ' - ' + s.end_time.substring(0,5) : ''">
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>
                </template>
            </div>

            <div x-data="{ openGuru: false, searchGuru: '' }" class="relative">
                <label class="app-label">Guru</label>
                <button type="button" @click="openGuru = !openGuru"
                    class="searchable-select-trigger">
                    <span x-text="filterGurus.length ? filterGurus.length + ' guru dipilih' : 'Semua Guru'"
                        :class="filterGurus.length ? 'text-primary font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-base-content/60 transition-transform duration-200"
                        :class="openGuru ? 'rotate-180' : ''"></i>
                </button>
                <template x-if="openGuru">
                    <div @click.outside="openGuru = false"
                        class="searchable-select-panel max-h-48 overflow-y-auto">
                        <div
                            class="searchable-select-search-wrap sticky top-0 z-10">
                            <input type="search" x-model="searchGuru" placeholder="Cari nama guru..."
                                class="input input-xs w-full">
                        </div>
                        <template
                            x-for="g in guruList.filter(item => item.name.toLowerCase().includes(searchGuru.toLowerCase()))"
                            :key="g.id">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-field px-3 py-2 transition hover:bg-primary/10">
                                <input type="checkbox" :value="g.id" x-model="filterGurus"
                                    class="checkbox checkbox-primary">
                                <span class="text-sm text-base-content" x-text="g.name"></span>
                            </label>
                        </template>
                    </div>
                </template>
            </div>

            <div x-data="{ openRuang: false, searchRuang: '' }" class="relative">
                <label
                    class="app-label">Ruang</label>
                <button type="button" @click="openRuang = !openRuang"
                    class="searchable-select-trigger">
                    <span x-text="filterRuangs.length ? filterRuangs.length + ' ruang dipilih' : 'Semua Ruang'"
                        :class="filterRuangs.length ? 'text-primary font-semibold' : ''"></span>
                    <i class="fas fa-chevron-down text-xs text-base-content/60 transition-transform duration-200"
                        :class="openRuang ? 'rotate-180' : ''"></i>
                </button>
                <template x-if="openRuang">
                    <div @click.outside="openRuang = false"
                        class="searchable-select-panel max-h-48 overflow-y-auto">
                        <div
                            class="searchable-select-search-wrap sticky top-0 z-10">
                            <input type="search" x-model="searchRuang" placeholder="Cari ruang..."
                                class="input input-xs w-full">
                        </div>
                        <template
                            x-for="r in ruangList.filter(item => item.name.toLowerCase().includes(searchRuang.toLowerCase()))"
                            :key="r.id">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-field px-3 py-2 transition hover:bg-primary/10">
                                <input type="checkbox" :value="r.id" x-model="filterRuangs"
                                    class="checkbox checkbox-primary">
                                <span class="text-sm text-base-content" x-text="r.name"></span>
                            </label>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex flex-wrap justify-between items-center gap-2 pt-2 border-t border-base-300">
            <label
                class="inline-flex items-center gap-2 text-xs font-bold text-base-content/70 cursor-pointer select-none">
                <input type="checkbox" @change="toggleSelectAll($el.checked)" :checked="isAllSelected()"
                    class="checkbox checkbox-primary">
                Pilih Semua yang Tampil
            </label>
            <button type="button" @click="exportExcel()" class="btn btn-export text-sm">
                <i class="fas fa-file-excel"></i> Export Excel <span x-show="hasActiveFilter"
                    class="text-[11px] bg-red-500 px-1.5 py-0.5 rounded"
                    x-text="'(' + filteredSiswa.length + ')'"></span>
            </button>
        </div>
    </div>

    <div
        class="bg-base-100 rounded-2xl border border-base-300 shadow-xs overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-base-300 bg-base-200 text-xs font-bold uppercase tracking-wider text-base-content/60 select-none">
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" @change="toggleSelectAll($el.checked)" :checked="isAllSelected()"
                                class="checkbox checkbox-primary">
                        </th>
                        <th class="p-4 cursor-pointer hover:bg-base-200 transition-colors"
                            @click="toggleSort('name')">
                            Siswa <i class="fas ml-1 text-[11px]"
                                :class="sortField === 'name' ? (sortOrder === 'asc' ? 'fa-sort-up' :
                                    'fa-sort-down') : 'fa-sort text-base-content/60'"></i>
                        </th>
                        <th class="p-4 cursor-pointer hover:bg-base-200 transition-colors"
                            @click="toggleSort('kelas')">
                            Kelas & Paket <i class="fas ml-1 text-[11px]"
                                :class="sortField === 'kelas' ? (sortOrder === 'asc' ? 'fa-sort-up' :
                                    'fa-sort-down') : 'fa-sort text-base-content/60'"></i>
                        </th>
                        <th class="p-4">Kontak</th>
                        <th class="p-4">Status & Kuota Pertemuan</th>
                        <th class="p-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300 text-sm">
                    <template x-for="siswa in filteredSiswa" :key="siswa.id">
                        <tr class="hover:bg-base-200/50 transition-colors group"
                            :class="{
                                'opacity-75 grayscale-[0.5]': viewMode === 'arsip',
                                'bg-primary/10': selectedSiswas
                                    .includes(siswa.id)
                            }">
                            <td class="p-4 text-center">
                                <label class="inline-flex p-3 -m-3 cursor-pointer">
                                    <input type="checkbox" :value="siswa.id" x-model="selectedSiswas"
                                        class="checkbox checkbox-primary">
                                </label>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shadow-inner shrink-0 text-sm font-bold"
                                        :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                            'bg-gradient-to-br from-warning to-error' :
                                            'bg-gradient-to-br from-primary to-accent'">
                                        <span x-text="siswa.name.charAt(0)"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-base-content truncate"
                                            :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                                'text-warning' : ''"
                                            x-text="siswa.name"></p>
                                        <p class="text-[11px] text-base-content/60 mt-0.5"
                                            x-text="'ID: #' + siswa.id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="space-y-1">
                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 uppercase tracking-wider bg-base-200 px-2 py-0.5 rounded">
                                        <i class="fas fa-id-badge opacity-60 text-[11px]"></i> <span
                                            x-text="siswa.kelas || 'N/A'"></span>
                                    </span>
                                    <template x-if="siswa.paket_pembayaran">
                                        <div class="text-[11px] font-bold uppercase tracking-wide truncate max-w-[150px]"
                                            :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                                'text-warning' : 'text-primary'">
                                            <span x-text="getPaketName(siswa.paket_pembayaran)"></span>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-4 text-base-content/70 font-medium">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-phone-alt text-[11px] text-base-content/60"></i>
                                    <span x-text="siswa.no_hp || '-'"></span>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col gap-1.5 max-w-xs">
                                    <div class="flex items-center justify-between text-xs font-semibold">
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-2 h-2 rounded-full"
                                                :class="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ?
                                                    'bg-warning animate-pulse' : 'bg-success') : 'bg-base-300'">
                                            </div>
                                            <span class="text-[11px] font-bold uppercase tracking-widest text-base-content/60"
                                                x-text="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'Incomplete' : 'Active') : 'Archived'"></span>
                                        </div>
                                        <span class="text-base-content/60 text-[11px]"
                                            x-text="getStatusJadwal(siswa).kuota > 0 ? getStatusJadwal(siswa).total + ' / ' + getStatusJadwal(siswa).kuota + ' Pertemuan' : 'Jadwal Belum Diatur'"></span>
                                    </div>
                                    <template x-if="getStatusJadwal(siswa).kuota > 0">
                                        <div
                                            class="w-full bg-base-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="getStatusJadwal(siswa).isKurang ? 'bg-warning' : 'bg-success'"
                                                :style="`width: ${Math.min((getStatusJadwal(siswa).total / getStatusJadwal(siswa).kuota) * 100, 100)}%`">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-1">
                                    <template x-if="viewMode === 'aktif'">
                                        <div class="flex gap-2">
                                            <button type="button" @click.stop="openDetail(siswa)"
                                                class="icon-action-primary relative" title="Detail & Catatan">
                                                <i class="fas fa-circle-info"></i>
                                                <span x-show="siswa.tandas && siswa.tandas.length > 0"
                                                    class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-400 border border-white"></span>
                                            </button>
                                            <button type="button" @click.stop="hapusSiswa(siswa.id)"
                                                class="icon-action-warning" title="Arsipkan">
                                                <i class="fas fa-box-archive"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="viewMode === 'arsip'">
                                        <div class="flex gap-2">
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
                            <td colspan="6" class="p-6">
                                <div class="app-empty border-0">
                                    <div class="app-empty-icon"><i class="fas fa-user-slash"></i></div>
                                    <p class="app-empty-title">Tidak ada data siswa yang ditemukan.</p>
                                    <p class="app-empty-text">Coba ubah kata pencarian atau tekan Reset pada panel filter di atas.</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-base-300">
            <template x-for="siswa in filteredSiswa" :key="siswa.id">
                <div class="p-4 space-y-3"
                    :class="{
                        'opacity-75 grayscale-[0.5]': viewMode === 'arsip',
                        'bg-primary/10': selectedSiswas.includes(siswa.id)
                    }">
                    <div class="flex items-start gap-3">
                        <label class="inline-flex shrink-0 p-1 -m-1 pt-2 cursor-pointer">
                            <input type="checkbox" :value="siswa.id" x-model="selectedSiswas"
                                class="checkbox checkbox-primary">
                        </label>
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shadow-inner shrink-0 text-sm font-bold"
                            :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                'bg-gradient-to-br from-warning to-error' :
                                'bg-gradient-to-br from-primary to-accent'">
                            <span x-text="siswa.name.charAt(0)"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-base-content truncate"
                                :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                    'text-warning' : ''"
                                x-text="siswa.name"></p>
                            <p class="text-[11px] text-base-content/60" x-text="'ID: #' + siswa.id"></p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <template x-if="viewMode === 'aktif'">
                                <div class="flex gap-2">
                                    <button type="button" @click.stop="openDetail(siswa)"
                                        class="icon-action-primary relative" title="Detail & Catatan">
                                        <i class="fas fa-circle-info"></i>
                                        <span x-show="siswa.tandas && siswa.tandas.length > 0"
                                            class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-400 border border-white"></span>
                                    </button>
                                    <button type="button" @click.stop="hapusSiswa(siswa.id)"
                                        class="icon-action-warning" title="Arsipkan">
                                        <i class="fas fa-box-archive"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="viewMode === 'arsip'">
                                <div class="flex gap-2">
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
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pl-12">
                        <span
                            class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 uppercase tracking-wider bg-base-200 px-2 py-0.5 rounded">
                            <i class="fas fa-id-badge opacity-60 text-[11px]"></i> <span
                                x-text="siswa.kelas || 'N/A'"></span>
                        </span>
                        <template x-if="siswa.paket_pembayaran">
                            <span class="text-[11px] font-bold uppercase tracking-wide truncate max-w-[140px]"
                                :class="getStatusJadwal(siswa).isKurang && viewMode === 'aktif' ?
                                    'text-warning' : 'text-primary'"
                                x-text="getPaketName(siswa.paket_pembayaran)"></span>
                        </template>
                        <span class="ml-auto flex items-center gap-1.5 text-xs text-base-content/70 font-medium">
                            <i class="fas fa-phone-alt text-[11px] text-base-content/60"></i>
                            <span x-text="siswa.no_hp || '-'"></span>
                        </span>
                    </div>

                    <div class="pl-12 space-y-1">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full"
                                    :class="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ?
                                        'bg-warning animate-pulse' : 'bg-success') : 'bg-base-300'">
                                </div>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-base-content/60"
                                    x-text="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'Incomplete' : 'Active') : 'Archived'"></span>
                            </div>
                            <span class="text-base-content/60 text-[11px]"
                                x-text="getStatusJadwal(siswa).kuota > 0 ? getStatusJadwal(siswa).total + ' / ' + getStatusJadwal(siswa).kuota + ' Pertemuan' : 'Jadwal Belum Diatur'"></span>
                        </div>
                        <template x-if="getStatusJadwal(siswa).kuota > 0">
                            <div class="w-full bg-base-200 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="getStatusJadwal(siswa).isKurang ? 'bg-warning' : 'bg-success'"
                                    :style="`width: ${Math.min((getStatusJadwal(siswa).total / getStatusJadwal(siswa).kuota) * 100, 100)}%`">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="filteredSiswa.length === 0">
                <div class="p-8 text-center text-base-content/60">
                    <i class="fas fa-user-slash text-3xl mb-2 block"></i>
                    Tidak ada data siswa yang ditemukan.
                </div>
            </template>
        </div>
    </div>

    <template x-if="showDetailModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            x-transition>
            <div @click="showDetailModal = false" class="absolute inset-0"></div>

            <div class="bg-base-100 rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden relative border transition-all duration-300 max-h-[90vh] flex flex-col"
                @click.stop>
                <div
                    class="p-4 border-b flex justify-between items-center bg-base-200 shrink-0">
                    <div>
                        <h3 class="font-bold text-base-content text-base sm:text-lg"
                            x-text="detailSiswa.name"></h3>
                        <p class="text-xs text-base-content/60">Profil siswa — data pokok diubah lewat
                            Workshop.</p>
                    </div>
                    <button type="button" @click="showDetailModal = false"
                        class="text-base-content/60 hover:text-base-content/70">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="p-4 sm:p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-[11px] font-bold text-base-content/60 uppercase tracking-wider">Panggilan
                                    </p>
                                    <p class="font-semibold text-base-content"
                                        x-text="detailSiswa.panggilan || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-base-content/60 uppercase tracking-wider">Kelas</p>
                                    <p class="font-semibold text-base-content"
                                        x-text="detailSiswa.kelas || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-base-content/60 uppercase tracking-wider">Nomor HP
                                    </p>
                                    <p class="font-semibold text-base-content"
                                        x-text="detailSiswa.no_hp || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-base-content/60 uppercase tracking-wider">Paket</p>
                                    <p class="font-semibold text-base-content"
                                        x-text="getPaketName(detailSiswa.paket_pembayaran)"></p>
                                </div>
                            </div>
                            <a :href="`{{ route('admin.workshop.index') }}?edit_siswa=${detailSiswa.id}`"
                                class="btn btn-neutral text-xs w-full justify-center">
                                <i class="fas fa-pen-to-square"></i> Ubah Data Pokok di Workshop
                            </a>

                            <div class="pt-3 border-t border-base-300">
                                <h4
                                    class="text-xs font-bold text-base-content/80 uppercase tracking-wider flex items-center gap-2 mb-3">
                                    <i class="fas fa-note-sticky text-warning"></i> Catatan
                                </h4>
                                <form @submit.prevent="simpanCatatan" class="flex gap-2 mb-3">
                                    <input type="text" x-model="catatanForm.keterangan" required
                                        placeholder="Tulis catatan baru..."
                                        class="flex-1 rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden">
                                    <button type="submit" class="btn btn-primary text-xs shrink-0"
                                        :disabled="isSavingCatatan">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <template x-for="t in detailSiswa.tandas || []" :key="t.id">
                                        <div
                                            class="p-2.5 bg-amber-50/60 dark:bg-amber-950/20 rounded-lg border border-amber-100 dark:border-amber-900/30 flex items-start justify-between gap-2 text-xs">
                                            <span class="text-base-content/80"
                                                x-text="t.keterangan"></span>
                                            <button type="button" @click="hapusCatatan(t.id)" :disabled="isSavingCatatan"
                                                class="text-error hover:text-error shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!detailSiswa.tandas || detailSiswa.tandas.length === 0">
                                        <p class="text-xs italic text-base-content/60 py-2">Belum ada catatan untuk siswa ini.
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div
                            class="p-4 sm:p-6 bg-base-200/50 space-y-4 border-t md:border-t-0 md:border-l border-base-300">
                            <h4
                                class="text-xs font-bold text-base-content/80 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-primary"></i> Jadwal Kelas Diikuti
                            </h4>
                            <div class="space-y-3 max-h-[300px] md:max-h-[400px] overflow-y-auto pr-1">
                                <template x-if="isLoadingJadwal">
                                    <div class="flex items-center justify-center gap-2 py-8 text-sm text-base-content/60">
                                        <i class="fas fa-spinner fa-spin text-primary"></i>
                                        <span>Memuat jadwal...</span>
                                    </div>
                                </template>
                                <template x-for="j in getSiswaJadwalList(detailSiswa.id)" :key="j.id">
                                    <div
                                        class="p-3 bg-base-100 rounded-xl border border-base-300 shadow-xs flex items-start gap-3">
                                        <div
                                            class="p-2 bg-primary/10 rounded-lg text-primary shrink-0">
                                            <i class="fas fa-clock text-sm"></i>
                                        </div>
                                        <div class="grow min-w-0">
                                            <p class="text-sm font-bold text-base-content truncate"
                                                x-text="j.mapel_name"></p>
                                            <p class="text-xs text-base-content/60 font-medium mt-0.5">
                                                <span class="capitalize" x-text="j.hari_name"></span> | <span
                                                    x-text="j.sesi_name"></span> (<span x-text="j.sesi_time"></span>)
                                            </p>
                                            <p
                                                class="text-xs text-base-content/60 mt-1 flex items-center gap-1">
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
                                        class="text-center py-8 border border-dashed border-base-300 rounded-xl bg-base-100">
                                        <i
                                            class="fas fa-calendar-times text-base-content/60 text-2xl mb-2"></i>
                                        <p class="text-xs text-base-content/60">Belum ada jadwal yang
                                            diatur untuk siswa ini.</p>
                                    </div>
                                </template>
                            </div>

                            <div class="pt-4 border-t border-base-300">
                                <h4
                                    class="text-xs font-bold text-base-content/80 uppercase tracking-wider flex items-center gap-2 mb-3">
                                    <i class="fas fa-chart-line text-success"></i> Rapor Perkembangan
                                </h4>

                                <template x-if="isLoadingRapor">
                                    <div class="flex items-center justify-center gap-2 py-6 text-sm text-base-content/60">
                                        <i class="fas fa-spinner fa-spin text-success"></i>
                                        <span>Memuat rapor...</span>
                                    </div>
                                </template>

                                <template x-if="!isLoadingRapor && raporSiswa && raporSiswa.ringkasan.total_pertemuan === 0">
                                    <div
                                        class="text-center py-6 border border-dashed border-base-300 rounded-xl bg-base-100">
                                        <i class="fas fa-chart-simple text-base-content/60 text-2xl mb-2"></i>
                                        <p class="text-xs text-base-content/60">Belum ada pertemuan yang
                                            dinilai untuk siswa ini.</p>
                                    </div>
                                </template>

                                <template x-if="!isLoadingRapor && raporSiswa && raporSiswa.ringkasan.total_pertemuan > 0">
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div
                                                class="rounded-xl border border-base-300 bg-base-100 p-2.5 text-center">
                                                <p class="text-lg font-black text-base-content"
                                                    x-text="raporSiswa.ringkasan.total_pertemuan"></p>
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-base-content/60">
                                                    Pertemuan</p>
                                            </div>
                                            <div
                                                class="rounded-xl border border-base-300 bg-base-100 p-2.5 text-center">
                                                <p class="text-lg font-black text-base-content"
                                                    x-text="raporSiswa.ringkasan.persen_kehadiran + '%'"></p>
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-base-content/60">
                                                    Kehadiran</p>
                                            </div>
                                            <div
                                                class="rounded-xl border border-base-300 bg-base-100 p-2.5 text-center">
                                                <p class="text-lg font-black text-base-content"
                                                    x-text="raporSiswa.ringkasan.rata_nilai ?? '-'"></p>
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-base-content/60">
                                                    Rata-rata Nilai</p>
                                            </div>
                                            <div
                                                class="rounded-xl border border-base-300 bg-base-100 p-2.5 text-center">
                                                <p class="text-lg font-black"
                                                    :class="{
                                                        'text-success': raporSiswa.ringkasan.tren === 'naik',
                                                        'text-error': raporSiswa.ringkasan.tren === 'turun',
                                                        'text-primary': raporSiswa.ringkasan.tren === 'stabil',
                                                        'text-base-content/60': !raporSiswa.ringkasan.tren
                                                    }"
                                                    x-text="labelTren(raporSiswa.ringkasan.tren)"></p>
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-base-content/60">
                                                    Tren Nilai</p>
                                            </div>
                                        </div>

                                        <div class="space-y-1.5">
                                            <template x-for="m in raporSiswa.per_mapel" :key="m.mapel">
                                                <div
                                                    class="flex items-center justify-between gap-2 rounded-lg bg-base-100 border border-base-300 px-3 py-2">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-bold text-base-content truncate"
                                                            x-text="m.mapel"></p>
                                                        <p class="text-[11px] text-base-content/60"
                                                            x-text="m.hadir + ' dari ' + m.jumlah_pertemuan + ' pertemuan hadir'">
                                                        </p>
                                                    </div>
                                                    <span
                                                        class="shrink-0 rounded-md bg-success/10 px-2 py-1 text-xs font-black text-success"
                                                        x-text="'Nilai ' + (m.rata_nilai ?? '-')"></span>
                                                </div>
                                            </template>
                                        </div>

                                        <a :href="`${routes.siswaBase}/${detailSiswa.id}/rapor/pdf`" target="_blank"
                                            class="btn btn-export text-xs w-full justify-center">
                                            <i class="fas fa-file-pdf"></i> Download Rapor PDF
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="p-4 border-t justify-end gap-2 bg-base-200 shrink-0 flex">
                    <button type="button" @click="showDetailModal = false"
                        class="btn btn-neutral text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
