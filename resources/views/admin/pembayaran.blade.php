<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700"
    x-data="pembayaranHandler({{ $pembayaranSummaries->toJson() }}, {{ $allSiswas->toJson() }}, {{ $pakets->toJson() }})">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-wallet text-emerald-500"></i>
                Ringkasan Tagihan Siswa
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total terdaftar tunggakan: <span
                    x-text="filteredSummaries.length"></span> Siswa</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button @click="prosesPenagihanMassal()" :disabled="isLoading"
                class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-file-invoice-dollar'"></i>
                <span x-text="isLoading ? 'Memproses...' : 'Penagihan Massal'"></span>
            </button>
            <button @click="openPaketModal()" :disabled="isLoading"
                class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-box"></i> Kelola Paket
            </button>
            <button @click="openAddPembayaran()" :disabled="isLoading"
                class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-plus"></i> Tambah Tagihan
            </button>
            <button @click="lunaskanSemua()" :disabled="isLoading"
                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-check-double'"></i>
                <span x-text="isLoading ? 'Loading...' : 'Selesaikan Seluruh Status'"></span>
            </button>
        </div>
    </div>

    <div
        class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border dark:border-gray-700">
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cari Nama / Keterangan</label>
            <input type="text" x-model="filterSearch" placeholder="Cari..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:ring-emerald-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Filter Bulan</label>
            <select x-model="filterBulan"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:ring-emerald-500">
                <option value="all">Semua Bulan</option>
                <option value="01">Januari</option>
                <option value="02">Februari</option>
                <option value="03">Maret</option>
                <option value="04">April</option>
                <option value="05">Mei</option>
                <option value="06">Juni</option>
                <option value="07">Juli</option>
                <option value="08">Agustus</option>
                <option value="09">September</option>
                <option value="10">Oktober</option>
                <option value="11">November</option>
                <option value="12">Desember</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">Status</label>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center text-xs dark:text-white cursor-pointer">
                    <input type="radio" x-model="filterStatus" value="all" class="text-emerald-500">
                    <span class="ml-1">Semua</span>
                </label>
                <label class="inline-flex items-center text-xs text-red-500 font-bold cursor-pointer">
                    <input type="radio" x-model="filterStatus" value="0" class="text-red-500">
                    <span class="ml-1">Belum</span>
                </label>
                <label class="inline-flex items-center text-xs text-orange-500 font-bold cursor-pointer">
                    <input type="radio" x-model="filterStatus" value="1" class="text-orange-500">
                    <span class="ml-1">Tertagih</span>
                </label>
                <label class="inline-flex items-center text-xs text-emerald-500 font-bold cursor-pointer">
                    <input type="radio" x-model="filterStatus" value="2" class="text-emerald-500">
                    <span class="ml-1">Lunas</span>
                </label>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-xl relative">
        <div x-show="isLoading"
            class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
            <i class="fas fa-circle-notch fa-spin fa-2x text-emerald-500"></i>
        </div>

        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Siswa</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Tagihan</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Keterangan</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="item in filteredSummaries" :key="item.id_siswa">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white"
                                x-text="item.siswa.name"></span>
                            <span class="block text-[10px] text-gray-400" x-text="item.siswa.kelas"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-md font-mono font-bold text-sm"
                                :class="{
                                    'bg-red-50 dark:bg-red-900/20 text-red-600': item.status == 0,
                                    'bg-orange-50 dark:bg-orange-900/20 text-orange-600': item.status == 1,
                                    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600': item.status == 2
                                }">
                                Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.total_harga)"></span>
                            </span>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate"
                                x-text="item.gabungan_keterangan || '-'"></p>
                            <template x-if="item.status != 2">
                                <span class="text-[9px] text-gray-400 block mt-0.5" x-text="item.tanggal_format"></span>
                            </template>
                            <template x-if="item.status == 2">
                                <div class="mt-1">
                                    <span
                                        class="text-[10px] bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 px-1.5 py-0.5 rounded font-bold">
                                        Lunas: <span x-text="item.tanggal_pembayaran"></span>
                                    </span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5 font-medium">
                                        Via: <span x-text="item.pembayaran_via == 1 ? 'Transfer' : 'Cash'"></span>
                                    </span>
                                </div>
                            </template>
                        </td>
                        <td class="px-6 py-4 text-center space-x-2">
                            <template x-if="item.status == 0">
                                <button @click="chatWhatsApp(item)" :disabled="isLoading"
                                    class="bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white px-3 py-1.5 rounded text-[10px] font-bold transition-all inline-flex items-center gap-1">
                                    <i class="fab fa-whatsapp"></i> Chat WA
                                </button>
                            </template>
                            <template x-if="item.status == 1">
                                <button @click="prosesBayarSiswa(item)" :disabled="isLoading"
                                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-3 py-1.5 rounded text-[10px] font-bold transition-all inline-flex items-center gap-1">
                                    <i class="fas fa-hand-holding-usd"></i> Bayar
                                </button>
                            </template>
                            <button
                                @click="Swal.fire({title: 'Rincian Tagihan', text: item.gabungan_keterangan, icon: 'info', background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#fff', color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'})"
                                class="text-blue-500 hover:underline text-xs font-bold uppercase tracking-widest">Detail</button>
                        </td>
                    </tr>
                </template>
                <template x-if="filteredSummaries.length === 0">
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 italic">
                            Data tidak ditemukan!
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div x-show="showAddModal"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showAddModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative border dark:border-gray-700"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white">Tambah Tagihan Manual</h3>
                <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <form @submit.prevent="simpanTagihan" class="p-6 space-y-4">
                <div class="relative" x-data="{ openSearch: false }">
                    <label class="block text-sm font-medium dark:text-gray-300">Pilih Siswa</label>
                    <div class="relative mt-1">
                        <input type="text" x-model="siswaSearchModal" @focus="openSearch = true"
                            @click.away="openSearch = false" placeholder="Cari nama..."
                            class="block w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <div x-show="openSearch && filteredSiswasForModal.length > 0"
                        class="absolute z-[120] w-full mt-1 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-xl max-h-48 overflow-y-auto"
                        x-transition>
                        <template x-for="s in filteredSiswasForModal" :key="s.id">
                            <button type="button"
                                @click="form.id_siswa = s.id; siswaSearchModal = s.name; openSearch = false"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:text-white border-b last:border-0 dark:border-gray-700 transition-colors">
                                <span x-text="s.name" class="font-semibold"></span> - <span x-text="s.kelas || 'N/A'"
                                    class="text-xs text-gray-500"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Gunakan Paket (Opsional)</label>
                    <select @change="applyPaket($event.target.value)"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                        <option value="">-- Pilih Paket --</option>
                        <template x-for="p in pakets" :key="p.id">
                            <option :value="p.id"
                                x-text="p.nama_paket + ' (Rp ' + new Intl.NumberFormat('id-ID').format(p.harga) + ')'">
                            </option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Harga (Rp)</label>
                    <input type="number" x-model.number="form.harga" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Keterangan</label>
                    <textarea x-model="form.keterangan" rows="3" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-2">
                    <button type="button" @click="showAddModal = false" :disabled="isLoading"
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:text-white">Batal</button>
                    <button type="submit" :disabled="isLoading"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-bold transition-all flex items-center gap-2">
                        <i x-show="isLoading" class="fas fa-spinner fa-spin"></i>
                        <span x-text="isLoading ? 'Menyimpan...' : 'Simpan Tagihan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showPaketModal"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showPaketModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative border dark:border-gray-700"
            @click.stop>
            <div
                class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2"><i
                        class="fas fa-box text-purple-500"></i> Kelola Paket Pembayaran</h3>
                <button @click="showPaketModal = false" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                        x-text="paketForm.id ? 'Edit Paket' : 'Tambah Paket Baru'"></h4>
                    <form @submit.prevent="savePaket" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nama
                                Paket</label>
                            <input type="text" x-model="paketForm.nama_paket" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                placeholder="Contoh: SPP Bulanan">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Harga
                                (Rp)</label>
                            <input type="number" x-model.number="paketForm.harga" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" :disabled="isLoading"
                                class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-bold shadow-lg transform active:scale-95 flex items-center justify-center gap-2">
                                <i x-show="isLoading" class="fas fa-spinner fa-spin"></i>
                                <span x-text="paketForm.id ? 'Update' : 'Simpan'"></span>
                            </button>
                            <button type="button" x-show="paketForm.id" @click="resetPaketForm"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium dark:text-white">Batal</button>
                        </div>
                    </form>
                </div>
                <div class="flex flex-col">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Daftar
                        Paket</h4>
                    <div class="space-y-3 overflow-y-auto max-h-[300px] pr-2 custom-scrollbar">
                        <template x-for="p in pakets" :key="p.id">
                            <div
                                class="group p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl flex justify-between items-center border border-gray-100 dark:border-gray-600 hover:border-purple-300 transition-all">
                                <div>
                                    <div class="text-sm font-bold text-gray-800 dark:text-white"
                                        x-text="p.nama_paket"></div>
                                    <div class="text-xs font-mono mt-1 text-purple-600 dark:text-purple-400"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(p.harga)"></div>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="editPaket(p)" :disabled="isLoading"
                                        class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg"><i
                                            class="fas fa-edit"></i></button>
                                    <button @click="deletePaket(p.id)" :disabled="isLoading"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><i class="fas"
                                            :class="isLoading ? 'fa-spinner fa-spin' : 'fa-trash'"></i></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pembayaranHandler', (initialSummaries, initialSiswas, initialPakets) => ({
                summaries: initialSummaries || [],
                siswas: initialSiswas || [],
                pakets: initialPakets || [],
                filterSearch: '',
                filterBulan: 'all',
                filterStatus: '0',
                showAddModal: false,
                showPaketModal: false,
                siswaSearchModal: '',
                isLoading: false,
                form: {
                    id_siswa: '',
                    harga: '',
                    keterangan: '',
                    status: 0
                },
                paketForm: {
                    id: null,
                    nama_paket: '',
                    harga: ''
                },

                refreshToTab() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', 'pembayaran');
                    window.location.href = url.toString();
                },

                get filteredSummaries() {
                    let rawFiltered = this.summaries.filter(item => {
                        const matchesSearch = this.filterSearch === '' ||
                            (item.siswa && item.siswa.name.toLowerCase().includes(this
                                .filterSearch.toLowerCase())) ||
                            (item.keterangan && item.keterangan.toLowerCase().includes(this
                                .filterSearch.toLowerCase()));
                        const matchesBulan = this.filterBulan === 'all' || item.bulan ===
                            this.filterBulan;
                        const matchesStatus = this.filterStatus === 'all' || item.status
                            .toString() === this.filterStatus;
                        return matchesSearch && matchesBulan && matchesStatus;
                    });

                    let grouped = {};
                    rawFiltered.forEach(item => {
                        if (!grouped[item.id_siswa]) {
                            grouped[item.id_siswa] = {
                                id_siswa: item.id_siswa,
                                siswa: item.siswa,
                                total_harga: 0,
                                gabungan_keterangan: [],
                                rincian_data: [],
                                status: item.status,
                                tanggal_pembayaran: item.tanggal_pembayaran,
                                pembayaran_via: item.pembayaran_via,
                                tanggal_format: item.tanggal_format
                            };
                        }
                        grouped[item.id_siswa].total_harga += parseInt(item.harga || 0);
                        grouped[item.id_siswa].gabungan_keterangan.push(item.keterangan);
                        grouped[item.id_siswa].rincian_data.push({
                            keterangan: item.keterangan,
                            harga: item.harga
                        });
                    });

                    return Object.values(grouped).map(g => ({
                        ...g,
                        gabungan_keterangan: g.gabungan_keterangan.filter(k => k).join(
                            ', ')
                    }));
                },

                get filteredSiswasForModal() {
                    if (!this.siswaSearchModal) return [];
                    return this.siswas.filter(s => s.name.toLowerCase().includes(this
                        .siswaSearchModal.toLowerCase()));
                },

                openAddPembayaran() {
                    this.form = {
                        id_siswa: '',
                        harga: '',
                        keterangan: '',
                        status: 0
                    };
                    this.siswaSearchModal = '';
                    this.showAddModal = true;
                },

                applyPaket(paketId) {
                    if (!paketId) return;
                    const p = this.pakets.find(x => x.id == paketId);
                    if (p) {
                        this.form.harga = p.harga;
                        this.form.keterangan = 'Pembayaran Paket ' + p.nama_paket;
                    }
                },

                async simpanTagihan() {
                    if (!this.form.id_siswa) return Swal.fire('Peringatan', 'Pilih siswa!',
                        'warning');
                    this.isLoading = true;
                    try {
                        const response = await fetch(`{{ route('admin.pembayaran.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menyimpan.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async prosesPenagihanMassal() {
                    const result = await Swal.fire({
                        title: 'Proses Penagihan?',
                        text: "Sistem akan membuat tagihan otomatis sesuai paket siswa.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#f97316',
                        confirmButtonText: 'Ya, Proses!',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000'
                    });

                    if (result.isConfirmed) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ route('admin.pembayaran.penagihanMassal') }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                async chatWhatsApp(item) {
                    const total = new Intl.NumberFormat('id-ID').format(item.total_harga);
                    const nama = item.siswa.name;
                    const noHp = item.siswa.no_hp;
                    if (!noHp) return Swal.fire('Error', 'No HP tidak ditemukan', 'error');

                    let rincianTeks = "";
                    item.rincian_data.forEach(d => {
                        rincianTeks +=
                            `*${d.keterangan || 'Tagihan'}* = Rp ${new Intl.NumberFormat('id-ID').format(d.harga)}\n`;
                    });

                    const text =
                        `*Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\nBapak/Ibu yang kami muliakan,\n\nKami dari pihak administrasi *E-Ling* mendoakan semoga Bapak/Ibu sekeluarga senantiasa dalam keadaan sehat dan dalam lindungan Allah SWT.\n\nMelalui pesan ini, kami bermaksud menyampaikan informasi mengenai kewajiban administrasi ananda *${nama}* dengan rincian sebagai berikut:\n\n${rincianTeks}\n*Total Tagihan: Rp ${total}*\n\nMohon Bapak/Ibu dapat segera menindaklanjuti informasi ini. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.\n\n*Jazakumullah Khairan Katsiran.*\n\n*Wassalamu'alaikum Warahmatullahi Wabarakatuh*`;

                    window.open(
                        `https://wa.me/${noHp.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(text)}`,
                        '_blank');

                    this.isLoading = true;
                    try {
                        await fetch(`{{ url('admin/pembayaran/lunas-siswa') }}/${item.id_siswa}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        this.refreshToTab();
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async prosesBayarSiswa(item) {
                    const {
                        value: formValues
                    } = await Swal.fire({
                        title: '<span class="text-xl font-bold">Konfirmasi Pembayaran</span>',
                        html: `
                            <div class="text-left space-y-4 px-2 pt-4">
                                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-xl flex items-start gap-3 border border-emerald-100 dark:border-emerald-800/30 mb-4">
                                    <i class="fas fa-info-circle text-emerald-600 mt-1"></i>
                                    <p class="text-[11px] text-emerald-800 dark:text-emerald-300 leading-relaxed">
                                        Anda akan memproses pelunasan untuk <strong>${item.siswa.name}</strong>. Pastikan nominal dan metode sudah sesuai.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Tanggal Pembayaran</label>
                                    <input id="swal-input1" type="date" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 transition-all dark:text-white" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Metode Pembayaran</label>
                                    <select id="swal-input2" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 transition-all dark:text-white appearance-none">
                                        <option value="0">💵 Tunai / Cash</option>
                                        <option value="1">🏦 Transfer Bank</option>
                                    </select>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Proses Bayar',
                        confirmButtonColor: '#059669',
                        background: document.documentElement.classList.contains('dark') ?
                            '#111827' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000',
                        preConfirm: () => ({
                            tanggal_pembayaran: document.getElementById(
                                'swal-input1').value,
                            pembayaran_via: document.getElementById('swal-input2')
                                .value
                        })
                    });

                    if (formValues) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ url('admin/pembayaran/bayar-siswa') }}/${item.id_siswa}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify(formValues)
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses pembayaran.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                async lunaskanSemua() {
                    const result = await Swal.fire({
                        title: 'Selesaikan Semua?',
                        text: "Semua tagihan (Belum & Tertagih) akan dianggap lunas.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'Ya, Lunaskan!',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000'
                    });

                    if (result.isConfirmed) {
                        this.isLoading = true;
                        try {
                            const response = await fetch(
                                `{{ route('admin.pembayaran.lunasSemua') }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            if ((await response.json()).status === 'success') this.refreshToTab();
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses.', 'error');
                        } finally {
                            this.isLoading = false;
                        }
                    }
                },

                openPaketModal() {
                    this.resetPaketForm();
                    this.showPaketModal = true;
                },

                resetPaketForm() {
                    this.paketForm = {
                        id: null,
                        nama_paket: '',
                        harga: ''
                    };
                },

                async savePaket() {
                    this.isLoading = true;
                    const url = this.paketForm.id ?
                        `{{ url('admin/paket') }}/${this.paketForm.id}` :
                        `{{ route('admin.paket.store') }}`;
                    const method = this.paketForm.id ? 'PUT' : 'POST';
                    try {
                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.paketForm)
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menyimpan paket.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                editPaket(p) {
                    this.paketForm = {
                        id: p.id,
                        nama_paket: p.nama_paket,
                        harga: p.harga
                    };
                },

                async deletePaket(id) {
                    if (!confirm('Hapus paket ini?')) return;
                    this.isLoading = true;
                    try {
                        const response = await fetch(`{{ url('admin/paket') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if ((await response.json()).status === 'success') this.refreshToTab();
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menghapus.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                }
            }));
        });
    </script>
@endpush
