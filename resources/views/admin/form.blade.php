<div class="bg-white dark:bg-gray-800 sticky top-0 z-10">
    <div class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-pen-to-square text-blue-500"></i>
            <span
                x-text="'Manajemen ' + (currentForm === 'tanda' ? 'Catatan Siswa' : currentForm.charAt(0).toUpperCase() + currentForm.slice(1))"></span>
        </h3>
        <button @click="currentForm = ''; formData = {}; activeFormTab = 'input'; formSearch = ''"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>

    <div class="grid grid-cols-2 border-b border-gray-200 dark:border-gray-700">
        <button @click="activeFormTab = 'input'"
            :class="activeFormTab === 'input' ?
                'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-gray-700' :
                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
            class="py-3 px-4 text-center border-b-2 font-medium text-sm transition-all duration-200">
            <i class="fas fa-plus-circle mr-2"></i> Input Data
        </button>
        <button @click="activeFormTab = 'list'"
            :class="activeFormTab === 'list' ?
                'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-gray-700' :
                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
            class="py-3 px-4 text-center border-b-2 font-medium text-sm transition-all duration-200">
            <i class="fas fa-list-ul mr-2"></i> Lihat Daftar
        </button>
    </div>
</div>

<form @submit.prevent="saveNewData" id="formTambahData" class="flex flex-col h-full">

    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">

        <div x-show="activeFormTab === 'input'" class="space-y-5">

            <template x-if="currentForm === 'mapel'">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Nama Mata
                            Pelajaran</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-book"></i></span>
                            <input type="text" x-model="formData.name" required
                                class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                                placeholder="Contoh: Matematika">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Warna Border</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="formData.border_color" required
                                class="h-10 w-20 p-1 rounded border border-gray-300 dark:border-gray-600">
                            <span class="text-xs text-gray-500">Pilih warna untuk jadwal.</span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'guru'">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Nama Guru</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                class="fas fa-chalkboard-teacher"></i></span>
                        <input type="text" x-model="formData.name" required
                            class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                            placeholder="Nama Lengkap Guru">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'ruang'">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Nama Ruang/Kelas</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                class="fas fa-building"></i></span>
                        <input type="text" x-model="formData.name" required
                            class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                            placeholder="Contoh: Lab Komputer">
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'siswa'">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Nama Siswa</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-user-graduate"></i></span>
                            <input type="text" x-model="formData.name" required
                                class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                                placeholder="Nama Lengkap Siswa">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Nama
                            Panggilan</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-id-badge"></i></span>
                            <input type="text" x-model="formData.panggilan"
                                class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                                placeholder="Contoh: Budi">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Kelas</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-graduation-cap"></i></span>
                            <input type="text" x-model="formData.kelas"
                                class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                                placeholder="Contoh: 10 SMA / 4 SD">
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'sesi'">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Nama Sesi</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-clock"></i></span>
                            <input type="text" x-model="formData.name" required
                                class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                                placeholder="Contoh: Sesi 1">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Mulai</label>
                            <input type="time" x-model="formData.start_time" required
                                class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Selesai</label>
                            <input type="time" x-model="formData.end_time" required
                                class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm">
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="currentForm === 'tanda'">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Pilih Siswa</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i
                                    class="fas fa-user"></i></span>
                            <select x-model.number="formData.siswa_id" required
                                class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm">
                                <option value="">-- Cari Nama Siswa --</option>
                                <template x-for="siswa in allSiswas" :key="siswa.id">
                                    <option :value="siswa.id" x-text="siswa.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Isi Catatan /
                            Tanda</label>
                        <textarea x-model="formData.keterangan" rows="3" required
                            class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm"
                            placeholder="Tulis catatan di sini..."></textarea>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="activeFormTab === 'list'" class="space-y-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" x-model="formSearch" placeholder="Cari data..."
                    class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white sm:text-sm">
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div
                    class="bg-gray-50 dark:bg-gray-700 px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-xs font-bold text-gray-500 uppercase flex justify-between">
                    <span>Data</span>
                    <span>Total: <span x-text="getFilteredList().length"></span></span>
                </div>

                <ul
                    class="divide-y divide-gray-100 dark:divide-gray-700 max-h-60 overflow-y-auto bg-white dark:bg-gray-800">
                    <template x-for="item in getFilteredList()" :key="item.id">
                        <li
                            class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex justify-between items-center group">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-700 dark:text-gray-200 font-medium"
                                    x-text="item.name"></span>

                                <template x-if="currentForm === 'sesi'">
                                    <span class="text-xs text-gray-400"
                                        x-text="item.start_time + ' - ' + item.end_time"></span>
                                </template>

                                <template x-if="currentForm === 'tanda'">
                                    <span class="text-xs text-gray-400"
                                        x-text="new Date(item.original_date).toLocaleDateString()"></span>
                                </template>
                            </div>

                            <div
                                class="flex items-center space-x-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-200">
                                <button type="button" @click="editDataItem(item)"
                                    class="text-blue-500 hover:text-blue-700 p-1.5 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                                    title="Edit Data">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>

                                <button type="button" @click="deleteDataItem(item.id)"
                                    class="text-red-500 hover:text-red-700 p-1.5 rounded-md hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                    title="Hapus Data">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </li>
                    </template>

                    <li x-show="getFilteredList().length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                        Tidak ada data ditemukan.
                    </li>
                </ul>
            </div>
        </div>

    </div>

    <div
        class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3 mt-auto border-t dark:border-gray-600">
        <button type="button" @click="currentForm = ''; formData = {}"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-500">
            Batal
        </button>

        <button x-show="activeFormTab === 'input'" type="submit" id="saveNewDataButton"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Simpan Data Baru
        </button>
    </div>
</form>
