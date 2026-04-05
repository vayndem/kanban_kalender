<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700"
    x-data="pembayaranHandler({{ $pembayaranSummaries->toJson() }}, {{ $allSiswas->toJson() }}, {{ $pakets->toJson() }})">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-wallet text-emerald-500"></i>
                Ringkasan Tagihan Siswa
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total terdaftar tunggakan: <span
                    x-text="summaries.length"></span> Siswa</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button @click="prosesPenagihanMassal()"
                class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-file-invoice-dollar"></i> Penagihan Massal
            </button>
            <button @click="openPaketModal()"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-box"></i> Kelola Paket
            </button>
            <button @click="openAddPembayaran()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-plus"></i> Tambah Tagihan
            </button>
            <button @click="lunaskanSemua()"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-check-double"></i> Selesaikan Seluruh Status
            </button>
        </div>
    </div>

    <div class="overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-xl">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Siswa</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total Tagihan</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Keterangan</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="item in summaries" :key="item.id_siswa">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white"
                                x-text="item.siswa.name"></span>
                            <span class="block text-[10px] text-gray-400" x-text="item.siswa.kelas"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 py-1 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-md font-mono font-bold text-sm">
                                Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.total_harga)"></span>
                            </span>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate"
                                x-text="item.gabungan_keterangan || '-'"></p>
                        </td>
                        <td class="px-6 py-4 text-center space-x-2">
                            <button @click="chatWhatsApp(item)"
                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-xs font-bold transition-all inline-flex items-center gap-1">
                                <i class="fab fa-whatsapp"></i> Chat WA
                            </button>
                            <button
                                @click="Swal.fire({title: 'Rincian Tagihan', text: item.gabungan_keterangan, icon: 'info', background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#fff', color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'})"
                                class="text-blue-500 hover:underline text-xs font-bold uppercase tracking-widest">Detail</button>
                        </td>
                    </tr>
                </template>
                <template x-if="summaries.length === 0">
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 italic">
                            Semua tagihan sudah lunas!
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
                <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i
                        class="fas fa-times fa-lg"></i></button>
            </div>
            <form @submit.prevent="simpanTagihan" class="p-6 space-y-4">
                <div class="relative" x-data="{ openSearch: false }">
                    <label class="block text-sm font-medium dark:text-gray-300">Pilih Siswa</label>
                    <div class="relative mt-1">
                        <input type="text" x-model="siswaSearchModal" @focus="openSearch = true"
                            @click.away="openSearch = false" placeholder="Cari nama siswa..."
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
                                <span x-text="s.name" class="font-semibold"></span>
                                <span x-text="' - ' + (s.kelas || 'N/A')" class="text-xs text-gray-500"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Harga (Rp)</label>
                    <input type="number" x-model.number="form.harga" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm"
                        placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium dark:text-gray-300">Keterangan</label>
                    <textarea x-model="form.keterangan" rows="3" required
                        class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 text-sm"
                        placeholder="Contoh: Modul, SPP, dll"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-2">
                    <button type="button" @click="showAddModal = false"
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:text-white transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md font-bold transition-colors">Simpan
                        Tagihan</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showPaketModal"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition
        style="display: none;">
        <div @click="showPaketModal = false" class="absolute inset-0"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative border border-gray-100 dark:border-gray-700"
            @click.stop>
            <div
                class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-box text-purple-500"></i>
                    Kelola Paket Pembayaran
                </h3>
                <button @click="showPaketModal = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                    <i class="fas fa-times fa-lg"></i>
                </button>
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
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500 text-sm transition-all"
                                placeholder="Contoh: SPP Bulanan">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Harga
                                (Rp)</label>
                            <input type="number" x-model.number="paketForm.harga" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500 text-sm transition-all"
                                placeholder="0">
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit"
                                class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg text-sm font-bold shadow-lg shadow-purple-500/20 transition-all transform active:scale-95"
                                x-text="paketForm.id ? 'Update' : 'Simpan'">
                            </button>
                            <button type="button" x-show="paketForm.id" @click="resetPaketForm"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
                <div class="flex flex-col">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Daftar
                        Paket</h4>
                    <div class="space-y-3 overflow-y-auto max-h-[300px] pr-2 custom-scrollbar">
                        <template x-for="p in pakets" :key="p.id">
                            <div
                                class="group p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl flex justify-between items-center border border-gray-100 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500 transition-all">
                                <div>
                                    <div class="text-sm font-bold text-gray-800 dark:text-white"
                                        x-text="p.nama_paket"></div>
                                    <div class="text-xs font-mono mt-1 text-purple-600 dark:text-purple-400"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(p.harga)"></div>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="editPaket(p)"
                                        class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"><i
                                            class="fas fa-edit"></i></button>
                                    <button @click="deletePaket(p.id)"
                                        class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"><i
                                            class="fas fa-trash"></i></button>
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
                summaries: initialSummaries,
                siswas: initialSiswas,
                pakets: initialPakets,
                showAddModal: false,
                showPaketModal: false,
                siswaSearchModal: '',
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

                async prosesPenagihanMassal() {
                    const result = await Swal.fire({
                        title: 'Proses Penagihan?',
                        text: "Sistem akan membuat tagihan otomatis untuk seluruh siswa sesuai paket yang mereka ambil.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#f97316',
                        confirmButtonText: 'Ya, Proses Sekarang!',
                        cancelButtonText: 'Batal',
                        background: document.documentElement.classList.contains('dark') ?
                            '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ?
                            '#fff' : '#000'
                    });

                    if (result.isConfirmed) {
                        try {
                            const response = await fetch(
                                `{{ route('admin.pembayaran.penagihanMassal') }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            const res = await response.json();
                            if (res.status === 'success') {
                                Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: res.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    })
                                    .then(() => window.location.reload());
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memproses penagihan massal.', 'error');
                        }
                    }
                },

                async savePaket() {
                    const url = this.paketForm.id ?
                        `{{ url('admin/paket') }}/${this.paketForm.id}` :
                        `{{ route('admin.paket.store') }}`;
                    const method = this.paketForm.id ? 'PUT' : 'POST';
                    try {
                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.paketForm)
                        });
                        if ((await response.json()).status === 'success') window.location.reload();
                    } catch (e) {
                        alert('Gagal memproses paket');
                    }
                },

                editPaket(p) {
                    this.paketForm = {
                        ...p
                    };
                },

                async deletePaket(id) {
                    if (!confirm('Hapus paket ini?')) return;
                    try {
                        const response = await fetch(`{{ url('admin/paket') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        if ((await response.json()).status === 'success') window.location.reload();
                    } catch (e) {
                        alert('Gagal menghapus');
                    }
                },

                async chatWhatsApp(item) {
                    const total = new Intl.NumberFormat('id-ID').format(item.total_harga);
                    const nama = item.siswa.name;
                    const noHp = item.siswa.no_hp;
                    if (!noHp) return Swal.fire('Error', 'No HP tidak ditemukan', 'error');

                    let rincianTeks = "";
                    item.rincian_data.forEach((data) => {
                        rincianTeks +=
                            `*${data.keterangan || 'Tagihan'}* = Rp ${new Intl.NumberFormat('id-ID').format(data.harga)}\n`;
                    });

                    const text =
                        `Halo, Selamat siang.\n\nKami dari pihak *E-Ling* menginformasikan bahwa anak bernama *${nama}* memiliki tagihan administrasi sebesar *Rp ${total}* dengan rincian sebagai berikut:\n\n${rincianTeks}\nMohon Bapak/Ibu dapat segera menindaklanjuti informasi ini. Terima kasih.`;
                    window.open(
                        `https://wa.me/${noHp.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(text)}`,
                        '_blank');

                    try {
                        await fetch(`{{ url('admin/pembayaran/lunas-siswa') }}/${item.id_siswa}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        window.location.reload();
                    } catch (e) {
                        console.error(e);
                    }
                },

                async simpanTagihan() {
                    if (!this.form.id_siswa) return Swal.fire('Peringatan', 'Pilih siswa!',
                        'warning');
                    try {
                        const response = await fetch(`{{ route('admin.pembayaran.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.form)
                        });
                        if ((await response.json()).status === 'success') window.location.reload();
                    } catch (e) {
                        alert('Gagal menyimpan tagihan');
                    }
                },

                async lunaskanSemua() {
                    if (!confirm('Selesaikan semua tunggakan?')) return;
                    try {
                        const response = await fetch(
                            `{{ route('admin.pembayaran.lunasSemua') }}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });
                        if ((await response.json()).status === 'success') window.location.reload();
                    } catch (e) {
                        alert('Gagal');
                    }
                }
            }));
        });
    </script>
@endpush
