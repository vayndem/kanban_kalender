<div class="bg-white dark:bg-gray-800 sticky top-0 z-10 rounded-t-xl transition-all duration-200">
    <div class="flex justify-between items-center p-4 md:p-5 border-b border-gray-100 dark:border-gray-700/70">
        <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-pen-to-square text-blue-500"></i>
            <span
                x-text="formData.id ? 'Edit ' + (currentForm === 'tanda' ? 'Catatan' : currentForm.charAt(0).toUpperCase() + currentForm.slice(1)) : 'Tambah ' + (currentForm === 'tanda' ? 'Catatan' : currentForm.charAt(0).toUpperCase() + currentForm.slice(1))"></span>
        </h3>
        <button @click="currentForm = ''; formData = {}; activeFormTab = 'input'; formSearch = ''"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>

    <div class="grid grid-cols-2 border-b border-gray-200 dark:border-gray-700">
        <button @click="activeFormTab = 'input'"
            :class="activeFormTab === 'input' ?
                'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-gray-700/50 font-bold' :
                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium'"
            class="py-3 px-4 text-center border-b-2 text-xs md:text-sm transition-all duration-200 flex items-center justify-center gap-2">
            <i class="fas fa-plus-circle text-sm"></i> Input Data
        </button>
        <button @click="activeFormTab = 'list'"
            :class="activeFormTab === 'list' ?
                'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-gray-700/50 font-bold' :
                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium'"
            class="py-3 px-4 text-center border-b-2 text-xs md:text-sm transition-all duration-200 flex items-center justify-center gap-2">
            <i class="fas fa-list-ul text-sm"></i> Lihat Daftar
        </button>
    </div>
</div>

<form @submit.prevent="saveNewData" id="formTambahData"
    class="flex flex-col h-full bg-white dark:bg-gray-800 rounded-b-xl">
    <div class="p-4 md:p-6 space-y-5 max-h-[65vh] overflow-y-auto">
        <div x-show="activeFormTab === 'input'" class="space-y-4">
            <template x-if="currentForm === 'mapel'">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama
                            Mata Pelajaran</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-book text-xs"></i></span>
                            <input type="text" x-model="formData.name" required
                                class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                                placeholder="Contoh: Matematika">
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Warna
                            Indikator</label>
                        <div
                            class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700/30 p-1.5 rounded-lg border dark:border-gray-600">
                            <input type="color" x-model="formData.border_color" required
                                class="h-8 w-12 rounded cursor-pointer border-0 bg-transparent">
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Klik wadah
                                warna</span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'guru'">
                <div>
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama
                        Guru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                class="fas fa-chalkboard-teacher text-xs"></i></span>
                        <input type="text" x-model="formData.name" required
                            class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                            placeholder="Nama Lengkap Guru">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'ruang'">
                <div>
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama
                        Ruang / Kelas</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                class="fas fa-building text-xs"></i></span>
                        <input type="text" x-model="formData.name" required
                            class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                            placeholder="Contoh: Lab Komputer">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'siswa'">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama
                            Lengkap Siswa</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-user-graduate text-xs"></i></span>
                            <input type="text" x-model="formData.name" required
                                class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                                placeholder="Nama Lengkap Paspor/Ijazah">
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama
                            Panggilan</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-id-badge text-xs"></i></span>
                            <input type="text" x-model="formData.panggilan"
                                class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                                placeholder="Contoh: Budi">
                        </div>
                    </div>
                    <div class="sm:col-span-3">
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tingkatan
                            Kelas</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-graduation-cap text-xs"></i></span>
                            <input type="text" x-model="formData.kelas"
                                class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                                placeholder="Contoh: 10 SMA">
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'sesi'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama
                            Label Sesi</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-clock text-xs"></i></span>
                            <input type="text" x-model="formData.name" required
                                class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                                placeholder="Contoh: Sesi 1">
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jam
                            Mulai</label>
                        <input type="time" x-model="formData.start_time" required
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jam
                            Selesai</label>
                        <input type="time" x-model="formData.end_time" required
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'tanda'">
                <div class="grid grid-cols-1 gap-4">
                    <div class="relative" x-data="{ openSiswaList: false }">
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Target
                            Siswa</label>
                        <div class="relative mt-1">
                            <select x-model.number="formData.siswa_id" required
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm pl-8">
                                <option value="">-- Pilih Nama Siswa Terdaftar --</option>
                                <template x-for="siswa in allSiswas" :key="siswa.id">
                                    <option :value="siswa.id" x-text="siswa.name"></option>
                                </template>
                            </select>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-user text-xs"></i></span>
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Isi
                            Deskripsi Catatan</label>
                        <textarea x-model="formData.keterangan" rows="4" required
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm"
                            placeholder="Tulis catatan perkembangan atau informasi penting di sini..."></textarea>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="activeFormTab === 'list'" class="space-y-4">
            <div class="relative">
                <input type="text" x-model="formSearch" placeholder="Ketik kata kunci pencarian..."
                    class="pl-9 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                        class="fas fa-search text-xs"></i></span>
            </div>
            <div class="border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                <div
                    class="bg-gray-50 dark:bg-gray-900/60 px-4 py-2.5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <span>Entitas Data</span>
                    <span
                        class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-mono text-[10px]">Total:
                        <span x-text="getFilteredList().length"></span></span>
                </div>
                <ul
                    class="divide-y divide-gray-100 dark:divide-gray-700/60 max-h-64 overflow-y-auto bg-white dark:bg-gray-800 custom-scrollbar">
                    <template x-for="item in getFilteredList()" :key="item.id">
                        <li
                            class="px-4 py-3 hover:bg-gray-50/80 dark:hover:bg-gray-700/20 flex justify-between items-center transition-colors">
                            <div class="flex flex-col min-w-0 flex-1 pr-4">
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate"
                                    x-text="item.name"></span>
                                <template x-if="currentForm === 'sesi'">
                                    <span
                                        class="text-[11px] text-gray-400 dark:text-gray-500 font-medium font-mono mt-0.5 flex items-center gap-1">
                                        <i class="far fa-clock"></i> <span
                                            x-text="item.start_time + ' - ' + item.end_time"></span>
                                    </span>
                                </template>
                            </div>
                            <div class="flex items-center gap-0.5 shrink-0">
                                <button type="button" @click="editDataItem(item)"
                                    class="text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/40 p-2 rounded-lg transition-colors"><i
                                        class="fas fa-pencil-alt text-xs"></i></button>
                                <button type="button" @click="deleteDataItem(item.id)"
                                    class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 p-2 rounded-lg transition-colors"><i
                                        class="fas fa-trash-alt text-xs"></i></button>
                            </div>
                        </li>
                    </template>
                    <template x-if="getFilteredList().length === 0">
                        <li
                            class="px-4 py-8 text-center text-xs text-gray-400 dark:text-gray-500 italic font-medium bg-gray-50/30 dark:bg-transparent">
                            Tidak ada data yang sesuai.</li>
                    </template>
                </ul>
            </div>
        </div>
    </div>

    <div
        class="px-4 md:px-6 py-4 bg-gray-50 dark:bg-gray-900/40 flex justify-end gap-2.5 mt-auto border-t border-gray-100 dark:border-gray-700 rounded-b-xl">
        <button type="button" @click="currentForm = ''; formData = {}"
            class="flex-1 sm:flex-none px-4 py-2 text-xs md:text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 active:scale-95 transition-all">
            Batal
        </button>
        <button x-show="activeFormTab === 'input'" type="submit" id="saveNewDataButton"
            class="flex-1 sm:flex-none px-5 py-2 text-xs md:text-sm font-bold text-white bg-green-600 border border-transparent rounded-lg shadow-md hover:bg-green-700 active:scale-95 transition-all flex items-center justify-center gap-1.5">
            <i class="fas fa-circle-check"></i>
            <span x-text="formData.id ? 'Simpan Perubahan' : 'Simpan Data Baru'"></span>
        </button>
    </div>
</form>
