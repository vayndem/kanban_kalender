<div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-6 rounded-xl shadow-inner" x-data="siswaHandler({{ $allSiswas->toJson() }}, {{ $allArsips->toJson() }}, {{ $pakets->toJson() }}, {{ $jadwalsData->toJson() }})">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas mr-3 text-blue-500"
                    :class="viewMode === 'aktif' ? 'fa-user-graduate' : 'fa-archive'"></i>
                <span x-text="viewMode === 'aktif' ? 'Data Master Siswa' : 'Arsip Data Siswa'"></span>
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Total: <span x-text="viewMode === 'aktif' ? allSiswas.length : allArsips.length"></span> Siswa
            </p>
        </div>

        <div class="flex flex-wrap md:flex-nowrap gap-3 items-center">
            <div class="flex bg-gray-200 dark:bg-gray-700 p-1 rounded-xl shadow-sm border dark:border-gray-600">
                <button @click="viewMode = 'aktif'"
                    :class="viewMode === 'aktif' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-300' :
                        'text-gray-500 dark:text-gray-400'"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                    AKTIF
                </button>
                <button @click="viewMode = 'arsip'"
                    :class="viewMode === 'arsip' ? 'bg-white dark:bg-gray-600 shadow-sm text-red-600 dark:text-red-300' :
                        'text-gray-500 dark:text-gray-400'"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                    ARSIP
                </button>
            </div>

            <div class="relative flex-grow">
                <input type="text" x-model="siswaSearch" placeholder="Cari nama atau kelas..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>

            <button x-show="viewMode === 'aktif'" @click="openTambah()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tambah
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="siswa in filteredSiswa" :key="siswa.id">
            <div x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700 overflow-hidden"
                :class="viewMode === 'arsip' ? 'opacity-75 grayscale-[0.5]' : ''">

                <div class="h-1 w-full transition-colors duration-500"
                    :class="getStatusJadwal(siswa).isKurang ? 'bg-orange-500 animate-pulse' : 'bg-blue-500'">
                </div>

                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-inner shrink-0 transition-transform group-hover:scale-110 duration-300"
                                :class="getStatusJadwal(siswa).isKurang ? 'bg-gradient-to-br from-orange-400 to-red-500' :
                                    'bg-gradient-to-br from-blue-500 to-indigo-600'">
                                <span class="text-lg font-bold" x-text="siswa.name.charAt(0)"></span>
                            </div>

                            <div class="overflow-hidden">
                                <h4 class="font-bold truncate text-base transition-colors duration-300"
                                    :class="getStatusJadwal(siswa).isKurang ? 'text-orange-500' :
                                        'text-gray-900 dark:text-white'"
                                    x-text="siswa.name">
                                </h4>
                                <p
                                    class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1 uppercase tracking-wider font-semibold">
                                    <i class="fas fa-id-badge opacity-50"></i>
                                    <span x-text="siswa.kelas || 'N/A'"></span>
                                </p>
                            </div>
                        </div>

                        <template x-if="siswa.paket_pembayaran">
                            <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md border"
                                :class="getStatusJadwal(siswa).isKurang ?
                                    'bg-orange-50 dark:bg-orange-900/20 text-orange-600 border-orange-200' :
                                    'bg-blue-50 dark:bg-blue-900/20 text-blue-600 border-blue-100'">
                                <span x-text="getPaketName(siswa.paket_pembayaran)"></span>
                            </span>
                        </template>
                    </div>

                    <div class="flex items-center gap-2 mb-5 text-gray-600 dark:text-gray-400">
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center">
                            <i class="fas fa-phone-alt text-[10px]"></i>
                        </div>
                        <span class="text-xs font-medium" x-text="siswa.no_hp || '-'"></span>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full"
                                :class="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'bg-orange-500' :
                                    'bg-green-500') : 'bg-gray-400'">
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400"
                                x-text="viewMode === 'aktif' ? (getStatusJadwal(siswa).isKurang ? 'Incomplete' : 'Active') : 'Archived'"></span>
                        </div>

                        <div class="flex gap-1">
                            <template x-if="viewMode === 'aktif'">
                                <div class="flex gap-1">
                                    <button @click="openEdit(siswa)"
                                        class="p-1.5 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <button @click="hapusSiswa(siswa.id)"
                                        class="p-1.5 text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-box-archive"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="viewMode === 'arsip'">
                                <div class="flex gap-1">
                                    <button @click="restoreSiswa(siswa.id)"
                                        class="p-1.5 text-green-500 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-rotate-left"></i>
                                    </button>
                                    <button @click="hapusPermanen(siswa.id)"
                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <template x-if="viewMode === 'aktif'">
                    <div class="text-[8px] text-white font-black text-center py-0.5 uppercase tracking-widest transition-colors duration-500"
                        :class="getStatusJadwal(siswa).isKurang ? '' : 'bg-blue-500'">
                        <span
                            x-text="getStatusJadwal(siswa).kuota > 0
                            ? getStatusJadwal(siswa).total + ' dari ' + getStatusJadwal(siswa).kuota + ' Pertemuan'
                            : 'Jadwal Belum Diatur'">
                        </span>
                    </div>
                </template>
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
                <button @click="showSiswaModal = false" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <form @submit.prevent="simpanSiswa" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Nama Lengkap</label>
                        <input type="text" x-model="siswaForm.name" required
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Panggilan</label>
                        <input type="text" x-model="siswaForm.panggilan"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Kelas</label>
                        <input type="text" x-model="siswaForm.kelas"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Nomor HP</label>
                    <input type="text" x-model="siswaForm.no_hp" placeholder="0812..."
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Paket Pembayaran</label>
                    <select x-model="siswaForm.paket_pembayaran"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Paket --</option>
                        <template x-for="paket in pakets" :key="paket.id">
                            <option :value="paket.id" x-text="paket.nama_paket"></option>
                        </template>
                    </select>
                </div>
                <div class="pt-4 flex justify-end gap-2">
                    <button type="button" @click="showSiswaModal = false"
                        class="px-4 py-2 text-sm border rounded-lg dark:text-white border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Batal</button>
                    <button type="submit"
                        class="px-6 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('siswaHandler', (initialSiswa, initialArsip, paketData, jadwalData) => ({
                allSiswas: initialSiswa || [],
                allArsips: initialArsip || [],
                pakets: paketData || [],
                allJadwals: jadwalData || [],
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

                get filteredSiswa() {
                    let data = this.viewMode === 'aktif' ? this.allSiswas : this.allArsips;
                    if (this.siswaSearch) {
                        const search = this.siswaSearch.toLowerCase();
                        data = data.filter(s => (s.name && s.name.toLowerCase().includes(search)) ||
                            (s.kelas && s.kelas.toLowerCase().includes(search)));
                    }
                    return data.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
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
                    let phone = this.siswaForm.no_hp ? this.siswaForm.no_hp.trim() : '';
                    if (phone) {
                        phone = phone.replace(/\D/g, '');
                        if (phone.startsWith('0')) {
                            phone = '+62' + phone.substring(1);
                        } else if (phone.startsWith('8')) {
                            phone = '+62' + phone;
                        }
                        this.siswaForm.no_hp = phone;
                    }

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
                }
            }));
        });
    </script>
@endpush
