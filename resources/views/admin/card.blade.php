<div class="bg-white dark:bg-gray-800 p-6 sm:p-8 rounded-xl shadow-lg max-w-4xl mx-auto">

    <h3
        class="text-2xl font-semibold text-gray-900 dark:text-white mb-6 border-b pb-4 border-gray-200 dark:border-gray-700">
        <i class="fas fa-newspaper mr-3 text-blue-500"></i>
        Manajemen Berita
    </h3>

    <form action="#" method="POST" @submit.prevent="alert('Fitur \'Simpan Berita\' belum terhubung ke database.')"
        class="space-y-6">

        @csrf

        <div>
            <label for="judul_berita" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Judul Berita
            </label>
            <input type="text" id="judul_berita" name="judul_berita"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500"
                placeholder="Judul artikel berita...">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="kategori_berita" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kategori
                </label>
                <select id="kategori_berita" name="kategori_berita"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500">
                    <option>Umum</option>
                    <option>Pengumuman</option>
                    <option>Akademik</option>
                    <option>Event Sekolah</option>
                </select>
            </div>

            <div>
                <label for="gambar_berita" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Gambar Sampul (Thumbnail)
                </label>
                <input type="file" id="gambar_berita" name="gambar_berita"
                    class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:font-semibold file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100 dark:file:bg-blue-900/50 dark:file:text-blue-300 dark:hover:file:bg-blue-900
                              dark:border-gray-600 dark:bg-gray-700">
            </div>
        </div>

        <div>
            <label for="konten_berita" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Isi Berita
            </label>
            <textarea id="konten_berita" name="konten_berita" rows="10"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500"
                placeholder="Tulis isi berita lengkap di sini... Idealnya, ini akan menjadi Rich Text Editor (seperti Trix atau CKEditor)."></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Publikasi</label>
            <div class="mt-2 flex space-x-6">
                <div class="flex items-center">
                    <input id="status_draft" name="status_berita" type="radio" value="draft" checked
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-blue-500">
                    <label for="status_draft" class="ml-2 block text-sm text-gray-900 dark:text-gray-200">
                        Simpan sebagai Draft
                    </LAbel>
                </div>
                <div class="flex items-center">
                    <input id="status_publish" name="status_berita" type="radio" value="published"
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-blue-500">
                    <label for="status_publish" class="ml-2 block text-sm text-gray-900 dark:text-gray-200">
                        Publikasikan
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700/50 mt-8">
            <button type="button"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500 mr-3">
                Reset Form
            </button>
            <button type="submit"
                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:ring-offset-gray-800">
                Simpan Berita
            </button>
        </div>
    </form>
</div>
