<div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-6 rounded-xl shadow-inner" x-data="siswaHandler({{ $allSiswas->toJson() }}, {{ $pakets->toJson() }})">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-user-graduate mr-3 text-blue-500"></i>
                Data Master Siswa
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total terdaftar: <span x-text="allSiswas.length"></span>
                Siswa</p>
        </div>

        <div class="flex gap-2">
            <div class="relative flex-grow">
                <input type="text" x-model="siswaSearch" placeholder="Cari nama atau kelas..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
            <button @click="openTambah()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tambah
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="siswa in filteredSiswa" :key="siswa.id">
            <div
                class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700 overflow-hidden relative">
                <div class="absolute top-3 right-3 flex flex-col items-end gap-1">
                    <span
                        class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-[10px] font-bold uppercase rounded-full tracking-wider"
                        x-text="siswa.kelas || 'N/A'"></span>
                    <template x-if="siswa.paket_pembayaran">
                        <span
                            class="px-2.5 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-[9px] font-bold uppercase rounded-full"
                            x-text="getPaketName(siswa.paket_pembayaran)"></span>
                    </template>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shrink-0">
                            <span class="text-xl font-bold" x-text="siswa.name.charAt(0)"></span>
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="font-bold text-gray-900 dark:text-white truncate" x-text="siswa.name"></h4>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400" x-text="siswa.no_hp || 'No HP -'">
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-sticky-note text-gray-400 text-xs"></i>
                                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                    <span x-text="siswa.tandas ? siswa.tandas.length : 0"></span> Catatan
                                </span>
                            </div>
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEdit(siswa)"
                                    class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"><i
                                        class="fas fa-edit"></i></button>
                                <button @click="hapusSiswa(siswa.id)"
                                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"><i
                                        class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="h-1 w-full bg-blue-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500">
                </div>
            </div>
        </template>
    </div>

    <div x-show="showSiswaModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        style="display: none;" x-transition>
        <div @click="showSiswaModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden relative border dark:border-gray-700"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white"
                    x-text="siswaForm.id ? 'Edit Data Siswa' : 'Tambah Siswa Baru'"></h3>
                <button @click="showSiswaModal = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <form @submit.prevent="simpanSiswa" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Nama Lengkap</label>
                        <input type="text" x-model="siswaForm.name" required
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Panggilan</label>
                        <input type="text" x-model="siswaForm.panggilan"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Kelas</label>
                        <input type="text" x-model="siswaForm.kelas"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Nomor HP (WhatsApp)</label>
                    <input type="text" x-model="siswaForm.no_hp" placeholder="0812..."
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Paket Pembayaran</label>
                    <select x-model="siswaForm.paket_pembayaran"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                        <option value="">-- Pilih Paket --</option>
                        <template x-for="paket in pakets" :key="paket.id">
                            <option :value="paket.id" x-text="paket.nama_paket"></option>
                        </template>
                    </select>
                </div>
                <div class="pt-4 flex justify-end gap-2">
                    <button type="button" @click="showSiswaModal = false"
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:text-white">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('siswaHandler', (initialData, paketData) => ({
                allSiswas: initialData || [],
                pakets: paketData || [],
                showSiswaModal: false,
                siswaSearch: '',
                // Inisialisasi awal objek form agar x-model tidak error
                siswaForm: {
                    id: null,
                    name: '',
                    panggilan: '',
                    kelas: '',
                    no_hp: '',
                    paket_pembayaran: ''
                },

                get filteredSiswa() {
                    if (!this.siswaSearch) return this.allSiswas;
                    const search = this.siswaSearch.toLowerCase();
                    return this.allSiswas.filter(s =>
                        (s.name && s.name.toLowerCase().includes(search)) ||
                        (s.kelas && s.kelas.toLowerCase().includes(search))
                    );
                },

                getPaketName(id) {
                    const p = this.pakets.find(x => x.id == id);
                    return p ? p.nama_paket : 'N/A';
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
                    // Gunakan operator OR untuk memastikan tidak ada field yang 'null'
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

                formatPhone(phone) {
                    if (!phone) return '';
                    let cleaned = phone.toString().trim().replace(/[^0-9]/g, '');
                    if (cleaned.startsWith('0')) cleaned = '62' + cleaned.substring(1);
                    else if (cleaned.startsWith('8')) cleaned = '62' + cleaned;
                    return '+' + cleaned;
                },

                async simpanSiswa() {
                    if (this.siswaForm.no_hp) this.siswaForm.no_hp = this.formatPhone(this.siswaForm
                        .no_hp);

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
                        if (res.status === 'success') window.location.reload();
                        else alert(res.message || 'Gagal menyimpan');
                    } catch (e) {
                        alert('Terjadi kesalahan sistem');
                    }
                },

                async hapusSiswa(id) {
                    if (!confirm('Hapus data siswa ini?')) return;
                    try {
                        const response = await fetch(`{{ url('admin/siswa') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if ((await response.json()).status === 'success') window.location.reload();
                    } catch (e) {
                        alert('Gagal menghapus');
                    }
                }
            }));
        });
    </script>
@endpush
