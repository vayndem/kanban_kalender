<div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
        x-text="'Tambah ' + (currentForm === 'tanda' ? 'Tanda Siswa' : currentForm.charAt(0).toUpperCase() + currentForm.slice(1))">
    </h3>
    <button @click="currentForm = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
        <i class="fas fa-times"></i>
    </button>
</div>

<form @submit.prevent="saveNewData" x-data="{ formData: {} }" id="formTambahData">
    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">

        <template x-if="currentForm === 'mapel'">
            <div>
                <!-- UBAH: dark:text-white -->
                <label for="mapel_name" class="block text-sm font-medium text-gray-700 dark:text-white">Nama Mata
                    Pelajaran</label>
                <input type="text" x-model="formData.name" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">

                <label for="mapel_color" class="block text-sm font-medium text-gray-700 dark:text-white mt-3">Warna
                    Border (Hex)</label>
                <input type="color" x-model="formData.border_color" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 h-10 p-1">
            </div>
        </template>

        <template x-if="currentForm === 'guru'">
            <div>
                <label for="guru_name" class="block text-sm font-medium text-gray-700 dark:text-white">Nama Guru</label>
                <input type="text" x-model="formData.name" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
            </div>
        </template>

        <template x-if="currentForm === 'ruang'">
            <div>
                <label for="ruang_name" class="block text-sm font-medium text-gray-700 dark:text-white">Nama
                    Ruang/Kelas</label>
                <input type="text" x-model="formData.name" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
            </div>
        </template>

        <template x-if="currentForm === 'siswa'">
            <div>
                <label for="siswa_name" class="block text-sm font-medium text-gray-700 dark:text-white">Nama
                    Siswa</label>
                <input type="text" x-model="formData.name" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
            </div>
        </template>

        <template x-if="currentForm === 'sesi'">
            <div>
                <label for="sesi_name" class="block text-sm font-medium text-gray-700 dark:text-white">Nama Sesi</label>
                <input type="text" x-model="formData.name" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">

                <label for="sesi_start" class="block text-sm font-medium text-gray-700 dark:text-white mt-3">Waktu
                    Mulai</label>
                <input type="time" x-model="formData.start_time" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">

                <label for="sesi_end" class="block text-sm font-medium text-gray-700 dark:text-white mt-3">Waktu
                    Selesai</label>
                <input type="time" x-model="formData.end_time" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
            </div>
        </template>

        <!-- === BAGIAN TANDA (Catatan Siswa) === -->
        <template x-if="currentForm === 'tanda'">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-white">Pilih Siswa</label>
                <!-- Dropdown text juga putih -->
                <select x-model.number="formData.siswa_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                    <option value="">-- Cari Nama Siswa --</option>
                    <template x-for="siswa in allSiswas" :key="siswa.id">
                        <option :value="siswa.id" x-text="siswa.name"></option>
                    </template>
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-300 mt-1">Pilih siswa yang ingin diberi catatan.</p>

                <label class="block text-sm font-medium text-gray-700 dark:text-white mt-4">Keterangan / Tanda</label>
                <textarea x-model="formData.keterangan" rows="3" required
                    placeholder="Contoh: Belum lunas SPP, Sakit, Perlu perhatian khusus..."
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white dark:placeholder-gray-400"></textarea>
            </div>
        </template>

    </div>

    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
        <button type="button" @click="currentForm = ''"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-500">
            Batal
        </button>

        <button type="submit" id="saveNewDataButton"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Simpan Data Baru
        </button>
    </div>
</form>
