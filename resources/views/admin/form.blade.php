<div class="bg-white dark:bg-gray-800 sticky top-0 z-20 rounded-t-2xl transition-all duration-200 shadow-sm">
    <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100 dark:border-gray-700/70">
        <h3 class="text-lg md:text-xl font-black text-gray-900 dark:text-white flex items-center gap-3">
            <div class="p-2.5 bg-blue-50 dark:bg-blue-950/50 rounded-xl text-blue-600 dark:text-blue-400">
                <i class="fas fa-cubes text-base md:text-lg"></i>
            </div>
            <span
                x-text="formData.id ? 'Edit ' + (currentForm === 'tanda' ? 'Catatan' : currentForm.charAt(0).toUpperCase() + currentForm.slice(1)) : 'Tambah ' + (currentForm === 'tanda' ? 'Catatan' : currentForm.charAt(0).toUpperCase() + currentForm.slice(1))"></span>
        </h3>
        <button @click="currentForm = ''; formData = {}; activeFormTab = 'input'; formSearch = ''"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-all p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl active:scale-95">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>

    <div
        class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20 p-1.5 gap-2 mx-6 my-3 rounded-xl">
        <button @click="activeFormTab = 'input'"
            :class="activeFormTab === 'input' ?
                'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-md font-bold' :
                'text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-800/50 font-medium'"
            class="flex-1 py-3 px-4 text-center rounded-lg text-xs md:text-sm transition-all duration-200 flex items-center justify-center gap-2.5">
            <i class="fas fa-edit text-base"></i> Form Isian Data
        </button>
        <button @click="activeFormTab = 'list'"
            :class="activeFormTab === 'list' ?
                'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-md font-bold' :
                'text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-800/50 font-medium'"
            class="flex-1 py-3 px-4 text-center rounded-lg text-xs md:text-sm transition-all duration-200 flex items-center justify-center gap-2.5">
            <i class="fas fa-folder-open text-base"></i> Database Terdaftar
        </button>
    </div>
</div>

<form @submit.prevent="saveNewData" id="formTambahData"
    class="flex flex-col h-full bg-white dark:bg-gray-800 rounded-b-2xl">
    <div class="px-6 py-4 space-y-6 max-h-[65vh] overflow-y-auto custom-scrollbar">
        <div x-show="activeFormTab === 'input'" class="space-y-5">
            <template x-if="currentForm === 'mapel'">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Mata
                            Pelajaran</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-book text-sm"></i>
                            </span>
                            <input type="text" x-model="formData.name" required
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                                placeholder="Contoh: Matematika Wajib">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Warna
                            Indikator</label>
                        <div
                            class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/30 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 h-[44px]">
                            <input type="color" x-model="formData.border_color" required
                                class="h-8 w-14 rounded-lg cursor-pointer border-0 bg-transparent">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold">Pilih Warna</span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'guru'">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Lengkap
                        Guru</label>
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="fas fa-chalkboard-teacher text-sm"></i>
                        </span>
                        <input type="text" x-model="formData.name" required
                            class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                            placeholder="Nama Lengkap beserta Gelar Akademik">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'ruang'">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Ruang /
                        Lokasi Kelas</label>
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="fas fa-building text-sm"></i>
                        </span>
                        <input type="text" x-model="formData.name" required
                            class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                            placeholder="Contoh: Ruang Teori 04 / Lab Utama">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'siswa'">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Lengkap
                            Siswa</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-user-graduate text-sm"></i>
                            </span>
                            <input type="text" x-model="formData.name" required
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                                placeholder="Nama Lengkap Resmi">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama
                            Panggilan</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-id-badge text-sm"></i>
                            </span>
                            <input type="text" x-model="formData.panggilan"
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                                placeholder="Panggilan Akrab">
                        </div>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Tingkatan /
                            Jenjang Kelas</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-graduation-cap text-sm"></i>
                            </span>
                            <input type="text" x-model="formData.kelas"
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                                placeholder="Contoh: XII MIPA 2 / 10 SMK">
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'sesi'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama / Label
                            Sesi</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-clock text-sm"></i>
                            </span>
                            <input type="text" x-model="formData.name" required
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none"
                                placeholder="Contoh: Sesi Pagi 01">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Waktu
                            Mulai</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-hourglass-start text-sm"></i>
                            </span>
                            <input type="time" x-model="formData.start_time" step="60" required
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Waktu
                            Selesai</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-hourglass-end text-sm"></i>
                            </span>
                            <input type="time" x-model="formData.end_time" step="60" required
                                class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none">
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'tanda'">
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Target Siswa
                            Terdaftar</label>
                        <div class="relative group">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <select x-model.number="formData.siswa_id" required
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none pl-10">
                                <option value="">-- Pilih Siswa --</option>
                                <template x-for="siswa in allSiswas" :key="siswa.id">
                                    <option :value="siswa.id" x-text="siswa.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Isi
                            Deskripsi Ringkasan Catatan</label>
                        <textarea x-model="formData.keterangan" rows="4" required
                            class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm p-3.5 focus:outline-none"
                            placeholder="Tulis informasi penting, rekam medis, kendala belajar, atau catatan khusus perkembangan di sini..."></textarea>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="activeFormTab === 'list'" class="space-y-4">
            <div class="relative group">
                <input type="text" x-model="formSearch"
                    placeholder="Ketik kata kunci untuk mencari data referensi..."
                    class="pl-10 block w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:text-white text-sm transition-all shadow-sm py-2.5 focus:outline-none">
                <span
                    class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                    <i class="fas fa-search text-sm"></i>
                </span>
            </div>

            <div
                class="border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm bg-white dark:bg-gray-800">
                <div
                    class="bg-gray-50 dark:bg-gray-900/60 px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <span class="flex items-center gap-2"><i class="fas fa-database text-blue-500"></i> Entitas
                        Terdata</span>
                    <span
                        class="bg-blue-50 dark:bg-gray-700 text-blue-600 dark:text-blue-400 px-3 py-1 rounded-full font-mono text-xs shadow-inner">
                        Total: <span class="font-bold" x-text="getFilteredList().length"></span>
                    </span>
                </div>
                <ul
                    class="divide-y divide-gray-100 dark:divide-gray-700/60 max-h-[35vh] overflow-y-auto bg-white dark:bg-gray-800 custom-scrollbar">
                    <template x-for="item in getFilteredList()" :key="item.id">
                        <li
                            class="px-5 py-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 flex justify-between items-center transition-all group/item">
                            <div class="flex flex-col min-w-0 flex-1 pr-4">
                                <span
                                    class="text-sm font-bold text-gray-800 dark:text-gray-200 group-hover/item:text-blue-600 dark:group-hover/item:text-blue-400 transition-colors truncate"
                                    x-text="item.name"></span>
                                <template x-if="currentForm === 'sesi'">
                                    <span
                                        class="text-xs text-gray-400 dark:text-gray-500 font-semibold font-mono mt-1 flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900/40 px-2 py-0.5 rounded w-max">
                                        <i class="far fa-clock text-blue-500"></i>
                                        <span x-text="item.start_time + ' - ' + item.end_time"></span>
                                    </span>
                                </template>
                            </div>
                            <div
                                class="flex items-center gap-1 shrink-0 opacity-80 group-hover/item:opacity-100 transition-opacity">
                                <button type="button" @click="editDataItem(item)"
                                    class="text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/50 p-2.5 rounded-xl transition-all active:scale-95 hover:shadow-sm">
                                    <i class="fas fa-pencil-alt text-sm"></i>
                                </button>
                                <button type="button" @click="deleteDataItem(item.id)"
                                    class="text-red-500 hover:bg-red-50 dark:hover:bg-red-950/50 p-2.5 rounded-xl transition-all active:scale-95 hover:shadow-sm">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </button>
                            </div>
                        </li>
                    </template>
                    <template x-if="getFilteredList().length === 0">
                        <li
                            class="px-5 py-14 text-center text-sm text-gray-400 dark:text-gray-500 italic font-medium bg-gray-50/10 dark:bg-transparent flex flex-col items-center justify-center gap-2">
                            <i class="fas fa-folder-open text-4xl text-gray-300 dark:text-gray-600"></i>
                            <span>Belum ada data referensi yang sesuai dengan kata kunci Anda.</span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>

    <div
        class="px-6 py-5 bg-gray-50 dark:bg-gray-900/40 flex justify-end gap-3 mt-auto border-t border-gray-100 dark:border-gray-700 rounded-b-2xl">
        <button type="button" @click="currentForm = ''; formData = {}"
            class="btn-neutral flex-1 sm:flex-none text-xs md:text-sm">
            Batal
        </button>
        <button x-show="activeFormTab === 'input'" type="submit" id="saveNewDataButton"
            class="btn-success flex-1 sm:flex-none px-7 text-xs md:text-sm">
            <i class="fas fa-circle-check text-sm"></i>
            <span x-text="formData.id ? 'Simpan Perubahan' : 'Simpan Data Baru'"></span>
        </button>
    </div>
</form>
