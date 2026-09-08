import { csrfToken } from '../core/http';
import { isDarkMode } from '../core/theme';

export const pembayaranHandler = ({
    initialSummaries,
    initialSiswas,
    initialPakets,
    initialDiskons,
    initialBatchStatus,
    routes,
}) => ({
    routes,
    summaries: initialSummaries || [],
    siswas: initialSiswas || [],
    pakets: initialPakets || [],
    diskons: initialDiskons || [],
    batchStatus: initialBatchStatus || null,
    filterSearch: '',
    filterBulan: String(new Date().getMonth() + 1).padStart(2, '0'),
    filterStatus: '0',
    showAddModal: false,
    showPaketModal: false,
    showDetailModal: false,
    showDiskonModal: false,
    siswaSearchModal: '',
    hpSearchModal: '',
    isLoading: false,
    isLoadingDetail: false,
    activeDetail: {},
    displayedSummaries: [],
    summaryStats: {
        totalFamilies: 0,
        totalNet: 0,
        totalPaid: 0,
        totalRemaining: 0
    },
    familyOptions: [],
    familyNamesByPhone: {},
    isDesktop: window.matchMedia('(min-width: 768px)').matches,
    form: {
        id_siswa: '',
        id_paket: null,
        harga: '',
        keterangan: '',
        status: 0
    },
    paketForm: {
        id: null,
        nama_paket: '',
        harga: '',
        pertemuan: 3
    },
    diskonForm: {
        id: null,
        no_hp: '',
        diskon: '',
        keterangan: '',
        is_universal: false
    },

    init() {
        const mediaQuery = window.matchMedia('(min-width: 768px)');
        mediaQuery.addEventListener('change', event => this.isDesktop = event.matches);
        this.buildFamilyIndex();
        this.rebuildSummaries();
        this.$watch('filterSearch', () => this.rebuildSummaries());
        this.$watch('filterBulan', () => this.rebuildSummaries());
        this.$watch('filterStatus', () => this.rebuildSummaries());
    },

    refreshToTab() {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'pembayaran');
        window.location.href = url.toString();
    },

    rebuildSummaries() {
        const result = AppDomain.buildPaymentSummary(this.summaries, this.diskons, {
            search: this.filterSearch,
            month: this.filterBulan,
            status: this.filterStatus
        });

        this.displayedSummaries = result.items;
        this.summaryStats = result.stats;
    },

    buildFamilyIndex() {
        const families = {};
        this.summaries.forEach(item => {
            const phone = item.no_hp || item.siswa?.no_hp;
            if (!phone || phone === 'N/A') return;
            if (!families[phone]) families[phone] = new Set();
            if (item.siswa?.name) families[phone].add(item.siswa.name);
        });

        this.familyNamesByPhone = Object.fromEntries(
            Object.entries(families).map(([phone, names]) => [phone, [...names].join(', ')])
        );
        this.familyOptions = Object.entries(this.familyNamesByPhone).map(([no_hp, siswa_names]) => ({
            no_hp,
            siswa_names
        }));
    },

    formatCurrency(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(amount || 0));
    },

    get periodeLabel() {
        return new Date().toLocaleString('id-ID', { month: 'long', year: 'numeric' });
    },

    /**
     * Peringatan dini tagihan ganda: dicek saat admin masih mengisi form,
     * bukan setelah tombol simpan ditolak server. Anchor yang dipakai sama
     * persis dengan yang dipakai penagihan massal (siswa + paket + periode).
     */
    get peringatanDuplikat() {
        if (!this.form.id_siswa || !this.form.id_paket) return null;

        const now = new Date();
        const periode = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

        const existing = this.summaries.find(item =>
            String(item.id_siswa) === String(this.form.id_siswa)
            && String(item.id_paket) === String(this.form.id_paket)
            && item.periode === periode
        );

        if (!existing) return null;

        const siswa = this.siswas.find(s => String(s.id) === String(this.form.id_siswa));
        const paket = this.pakets.find(p => String(p.id) === String(this.form.id_paket));
        const statusLabel = ['belum dibayar', 'sebagian sudah dibayar', 'sudah lunas'][existing.status] || '-';

        return `${siswa?.name || 'Siswa ini'} sudah punya tagihan paket ${paket?.nama_paket || 'ini'} `
            + `untuk periode ${this.periodeLabel}, dibuat ${existing.tanggal_format} `
            + `sebesar ${this.formatCurrency(existing.harga)} (status: ${statusLabel}).`;
    },

    get filteredSiswasForModal() {
        if (!this.siswaSearchModal) return [];
        return this.siswas.filter(s => s.name.toLowerCase().includes(this
            .siswaSearchModal.toLowerCase()));
    },

    get filteredFamiliesForModal() {
        if (!this.hpSearchModal) return this.familyOptions;
        return this.familyOptions.filter(f =>
            f.no_hp.includes(this.hpSearchModal) ||
            f.siswa_names.toLowerCase().includes(this.hpSearchModal.toLowerCase())
        );
    },

    getKeluargaLabelByHp(hp) {
        if (hp === null) return 'Seluruh Siswa Terdaftar (Universal)';
        return this.familyNamesByPhone[hp] || 'Anggota tidak terdeteksi';
    },

    async openDetailModal(item) {
        this.activeDetail = {
            ...item,
            raw_items: [],
            payment_details: []
        };
        this.showDetailModal = true;
        this.isLoadingDetail = true;

        try {
            const params = new URLSearchParams();
            const ids = (item.raw_items || []).map(row => row.id).filter(Boolean);
            if (ids.length) params.set('ids', ids.join(','));

            const response = await fetch(
                `/admin/pembayaran/keluarga/${encodeURIComponent(item.no_hp)}/detail?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Gagal mengambil rincian keluarga.');
            }

            this.activeDetail = {
                ...item,
                ...payload.data
            };
        } catch (error) {
            this.showDetailModal = false;
            AppSwal.error(error.message || 'Gagal memuat rincian pembayaran.');
        } finally {
            this.isLoadingDetail = false;
        }
    },

    buildStrukUrl(item) {
        const params = new URLSearchParams();
        const ids = (item.raw_items || []).map(row => row.id).filter(Boolean);
        if (ids.length) params.set('ids', ids.join(','));
        if (this.filterSearch) params.set('search', this.filterSearch);
        if (this.filterBulan) params.set('bulan', this.filterBulan);
        if (this.filterStatus) params.set('status', this.filterStatus);

        const query = params.toString();
        const base = `/admin/pembayaran/struk/${encodeURIComponent(item.no_hp)}`;
        return query ? `${base}?${query}` : base;
    },

    openAddPembayaran() {
        this.form = {
            id_siswa: '',
            id_paket: null,
            harga: '',
            keterangan: '',
            status: 0
        };
        this.siswaSearchModal = '';
        this.showAddModal = true;
    },

    applyPaket(paketId) {
        // id_paket adalah anchor anti-tagihan-ganda: dengan ini penagihan massal
        // tahu siswa ini sudah tertagih paket tsb bulan ini, walau teks
        // keterangannya berbeda. Dikosongkan bila admin memilih "tanpa paket".
        if (!paketId) {
            this.form.id_paket = null;
            return;
        }
        const p = this.pakets.find(x => x.id == paketId);
        if (p) {
            this.form.id_paket = p.id;
            this.form.harga = p.harga;
            this.form.keterangan =
                `Pembayaran Paket ${p.nama_paket} (${p.pertemuan} Pertemuan)`;
        }
    },

    async simpanTagihan() {
        if (!this.form.id_siswa) return AppSwal.error('Pilih target siswa terlebih dahulu.');
        if (!this.form.harga || Number(this.form.harga) <= 0) {
            return AppSwal.error('Nominal tagihan wajib lebih besar dari Rp 0.');
        }

        if (this.peringatanDuplikat) {
            const lanjut = await AppSwal.confirm(
                'Tagihan ini kemungkinan ganda!',
                `${this.peringatanDuplikat} Membuat tagihan lagi berarti siswa ini ditagih dua kali untuk hal yang sama. `
                + 'Kalau uangnya sudah masuk, tutup form ini dan gunakan "Catat Bayar" pada tagihan yang sudah ada.',
                'Saya mengerti, tetap lanjut'
            );
            if (!lanjut.isConfirmed) return;
        }

        const confirmation = await AppSwal.confirm(
            'Buat tagihan baru?',
            'Sistem akan mencatat komponen tagihan baru untuk siswa yang dipilih. Pastikan nominal dan keterangan sudah benar.',
            'Ya, simpan tagihan'
        );
        if (!confirmation.isConfirmed) return;

        this.isLoading = true;
        try {
            const response = await fetch(this.routes.pembayaranStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
            });
            const payload = await response.json();
            if (payload.status === 'success') {
                this.refreshToTab();
            } else {
                AppSwal.error(payload.message || 'Gagal menyimpan komponen tagihan.');
            }
        } catch (e) {
            AppSwal.error('Gagal menyimpan komponen tagihan.');
        } finally {
            this.isLoading = false;
        }
    },

    async prosesPenagihanMassal() {
        if (this.batchStatus?.penagihan_massal) {
            return AppSwal.error(
                `Penagihan massal periode ${this.periodeLabel} sudah dijalankan pada `
                + `${this.batchStatus.penagihan_massal.dijalankan_pada}`
                + `${this.batchStatus.penagihan_massal.oleh ? ' oleh ' + this.batchStatus.penagihan_massal.oleh : ''}, `
                + `menghasilkan ${this.batchStatus.penagihan_massal.jumlah_diproses} tagihan. `
                + 'Hanya boleh sekali per bulan supaya tidak ada tagihan ganda. '
                + 'Untuk siswa baru di tengah bulan, buat tagihan satu per satu lewat tombol "Tagihan".'
            );
        }

        const result = await AppSwal.confirm(
            `Jalankan penagihan massal ${this.periodeLabel}?`,
            'Sistem akan membuat tagihan bulanan untuk semua siswa yang punya paket terdaftar. '
            + 'Aksi ini hanya bisa dijalankan SEKALI dalam bulan ini — setelah dijalankan, tombolnya terkunci sampai bulan depan. '
            + 'Siswa yang sudah punya tagihan paket yang sama bulan ini otomatis dilewati.',
            'Ya, jalankan sekarang'
        );

        if (result.isConfirmed) {
            this.isLoading = true;
            try {
                const response = await fetch(
                    this.routes.penagihanMassal, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json'
                        }
                    });
                const payload = await response.json();
                if (payload.status === 'success') {
                    this.refreshToTab();
                } else {
                    AppSwal.error(payload.message || 'Gagal memproses pembuatan otomatis.');
                }
            } catch (e) {
                AppSwal.error('Gagal memproses pembuatan otomatis.');
            } finally {
                this.isLoading = false;
            }
        }
    },

    async chatWhatsApp(item) {
        const total = new Intl.NumberFormat('id-ID').format(item.total_akhir);
        const nama = item.siswa_names;
        const noHp = item.no_hp;
    
        if (!noHp || noHp === 'N/A') return AppSwal.error('No HP tidak valid.');
    
        const now = new Date();
        const bulan = now.toLocaleString('id-ID', {
            month: 'long',
            year: 'numeric'
        }).toUpperCase();
        const namaBulan = now.toLocaleString('id-ID', {
            month: 'long'
        });
        const tahun = now.getFullYear();
    
        let rincianTeks = "";
        item.raw_items.forEach(d => {
            rincianTeks += `* Tagihan : Rp ${new Intl.NumberFormat('id-ID').format(d.harga)} (${d.keterangan || '-'})\n`;
        });
    
        if (item.nominal_diskon > 0) {
            rincianTeks += `* Potongan Diskon : - Rp ${new Intl.NumberFormat('id-ID').format(item.nominal_diskon)} (${item.keterangan_diskon})\n`;
        }
    
        const text = `Pemberitahuan Administrasi Pembayaran\n` +
            `E-LING COURSE\n\n` +
            `Anggota keluarga siswa : ${nama}\n` +
            `Nomor HP : ${noHp}\n` +
            `Periode : ${bulan}\n\n` +
            `Rincian tagihan:\n` +
            `${rincianTeks}\n` +
            `Total kewajiban bersih : Rp ${total},--\n\n` +
            `Mohon penyelesaian pembayaran paling lambat 10 ${namaBulan} ${tahun}.\n` +
            `Silakan konfirmasi kepada admin apabila pembayaran sudah dilakukan.\n\n` +
            `Terima kasih atas perhatian dan kerja samanya.\n` +
            `E-Ling Course`;
    
        const waTarget = String(noHp).replace(/\D/g, '');
        window.open(`https://wa.me/${waTarget}?text=${encodeURIComponent(text)}`, '_blank');
    
        this.isLoading = true;
        try {
            await fetch(`${this.routes.lunasSiswaBase}/${item.id_siswa}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
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
        const totalTagihan = item.total_akhir;
        const sudahDibayar = item.total_sudah_dibayar;
        const sisaTagihan = totalTagihan - sudahDibayar;
        if (sisaTagihan <= 0) {
            return AppSwal.error('Tagihan ini sudah tidak memiliki sisa kewajiban.');
        }
        const isDark = isDarkMode();
        const paidPercent = totalTagihan > 0 ? Math.min(100, Math.round((sudahDibayar / totalTagihan) * 100)) : 0;

        const {
            value: formValues
        } = await Swal.fire({
            title: 'Pencatatan Penerimaan Pembayaran',
            html: `
                <div style="text-align: left; font-family: inherit;" class="space-y-4">
                    <div style="background-color: ${isDark ? '#374151' : '#f3f4f6'}; border: 1px solid ${isDark ? '#4b5563' : '#e5e7eb'};" class="p-3.5 rounded-xl space-y-2.5">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Total Tagihan Bersih</span>
                            <span style="font-size: 13px; font-weight: 800; color: ${isDark ? '#e5e7eb' : '#111827'};">Rp ${new Intl.NumberFormat('id-ID').format(totalTagihan)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Sudah Dibayar Sebelumnya</span>
                            <span style="font-size: 13px; font-weight: 800; color: #10b981;">Rp ${new Intl.NumberFormat('id-ID').format(sudahDibayar)} (${paidPercent}%)</span>
                        </div>
                        <div style="width: 100%; height: 8px; background-color: ${isDark ? '#1f2937' : '#e5e7eb'}; border-radius: 999px; overflow: hidden;">
                            <div style="height: 100%; width: ${paidPercent}%; background-color: #10b981; border-radius: 999px;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: baseline; border-top: 1px dashed ${isDark ? '#4b5563' : '#d1d5db'}; padding-top: 8px;">
                            <span style="font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Sisa Kewajiban Saat Ini</span>
                            <span style="font-size: 18px; font-weight: 900; color: #f59e0b;">Rp ${new Intl.NumberFormat('id-ID').format(sisaTagihan)}</span>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Nominal Pembayaran Diterima (Rp)</label>
                        <input id="swal-nominal" type="number" style="width: 100%; border-radius: 12px; padding: 10px 14px; font-size: 14px; background-color: ${isDark ? '#1f2937' : '#fff'}; border: 1px solid ${isDark ? '#4b5563' : '#d1d5db'}; color: ${isDark ? '#fff' : '#000'}; focus:outline-none;" value="${sisaTagihan > 0 ? sisaTagihan : ''}">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Keterangan Transaksi</label>
                        <input id="swal-keterangan" type="text" style="width: 100%; border-radius: 12px; padding: 10px 14px; font-size: 14px; background-color: ${isDark ? '#1f2937' : '#fff'}; border: 1px solid ${isDark ? '#4b5563' : '#d1d5db'}; color: ${isDark ? '#fff' : '#000'}; focus:outline-none;" value="Pembayaran cicilan / pelunasan">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Tanggal Bayar</label>
                            <input id="swal-tanggal" type="date" style="width: 100%; border-radius: 12px; padding: 10px 14px; font-size: 14px; background-color: ${isDark ? '#1f2937' : '#fff'}; border: 1px solid ${isDark ? '#4b5563' : '#d1d5db'}; color: ${isDark ? '#fff' : '#000'}; focus:outline-none;" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Metode Via</label>
                            <select id="swal-via" style="width: 100%; border-radius: 12px; padding: 10px 14px; font-size: 14px; background-color: ${isDark ? '#1f2937' : '#fff'}; border: 1px solid ${isDark ? '#4b5563' : '#d1d5db'}; color: ${isDark ? '#fff' : '#000'}; focus:outline-none; height: 44px;">
                                <option value="0">💵 Tunai / Cash</option>
                                <option value="1">🏦 Transfer Bank</option>
                            </select>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save mr-1.5"></i> Simpan Penerimaan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#4b5563',
            background: isDark ? '#111827' : '#fff',
            color: isDark ? '#fff' : '#000',
            customClass: {
                popup: 'rounded-2xl border dark:border-gray-700 shadow-2xl w-full max-w-xl p-6'
            },
            preConfirm: () => {
                const nom = document.getElementById('swal-nominal').value;
                if (!nom || parseInt(nom) <= 0) {
                    Swal.showValidationMessage(
                        'Nominal wajib diisi dengan benar!');
                    return false;
                }
                return {
                    nominal: nom,
                    keterangan_detail: document.getElementById(
                        'swal-keterangan').value,
                    tanggal_pembayaran: document.getElementById(
                        'swal-tanggal').value,
                    pembayaran_via: document.getElementById('swal-via')
                        .value
                }
            }
        });

        if (formValues) {
            this.isLoading = true;
            try {
                const response = await fetch(
                    `${this.routes.bayarSiswaBase}/${item.id_siswa_trigger}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(formValues)
                    });
                if ((await response.json()).status === 'success') this.refreshToTab();
            } catch (e) {
                AppSwal.error('Gagal mencatat data transaksi kas masuk.');
            } finally {
                this.isLoading = false;
            }
        }
    },

    async ubahKeLunas(item) {
        const result = await AppSwal.confirm(
            'Ubah ke lunas?',
            'Seluruh komponen tagihan dari keluarga ini akan ditutup menjadi lunas penuh. Jika masih ada sisa, sistem akan menambahkan pelunasan otomatis dengan keterangan "Selesai sistem".',
            'Ya, set lunas'
        );

        if (result.isConfirmed) {
            this.isLoading = true;
            try {
                const response = await fetch(
                    `${this.routes.keLunasMassalBase}/${item.id_siswa_trigger}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json'
                        }
                    });
                if ((await response.json()).status === 'success') this.refreshToTab();
            } catch (e) {
                AppSwal.error('Gagal memproses pembaruan status lunas massal.');
            } finally {
                this.isLoading = false;
            }
        }
    },

    openDiskonManagerModal() {
        this.resetDiskonForm();
        this.showDiskonModal = true;
    },

    resetDiskonForm() {
        this.diskonForm = {
            id: null,
            no_hp: '',
            diskon: '',
            keterangan: '',
            is_universal: false
        };
        this.hpSearchModal = '';
    },

    editDiskon(d) {
        const isGlobal = d.no_hp === null;
        this.diskonForm = {
            id: d.id,
            no_hp: d.no_hp || '',
            diskon: d.diskon,
            keterangan: d.keterangan || '',
            is_universal: isGlobal
        };
        this.hpSearchModal = isGlobal ? 'SEMUA KELUARGA TERDAFTAR (UNIVERSAL)' :
            `${d.no_hp} - (${this.getKeluargaLabelByHp(d.no_hp)})`;
    },

    async simpanDiskon() {
        if (!this.diskonForm.is_universal && !this.diskonForm.no_hp) {
            return AppSwal.error('Silakan pilih nomor HP keluarga target.');
        }
        this.isLoading = true;

        const isEdit = this.diskonForm.id !== null;
        const url = isEdit ? `${this.routes.diskonBase}/${this.diskonForm.id}` :
            this.routes.diskonStore;
        const method = isEdit ? 'PUT' : 'POST';

        const payload = {
            ...this.diskonForm,
            _token: csrfToken()
        };

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            if ((await response.json()).status === 'success') this.refreshToTab();
        } catch (e) {
            AppSwal.error('Gagal memproses data pembaruan diskon.');
        } finally {
            this.isLoading = false;
        }
    },

    async hapusDiskon(id) {
        const confirmation = await AppSwal.confirm('Hapus diskon?', 'Aturan potongan ini akan dihapus dari sistem.', 'Ya, hapus diskon');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const response = await fetch(`${this.routes.diskonBase}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                }
            });
            if ((await response.json()).status === 'success') this.refreshToTab();
        } catch (e) {
            AppSwal.error('Gagal merestore status potongan diskon.');
        } finally {
            this.isLoading = false;
        }
    },

    async lunaskanSemua() {
        if (this.batchStatus?.pelunasan_massal) {
            return AppSwal.error(
                `Penyelesaian seluruh status periode ${this.periodeLabel} sudah dijalankan pada `
                + `${this.batchStatus.pelunasan_massal.dijalankan_pada}`
                + `${this.batchStatus.pelunasan_massal.oleh ? ' oleh ' + this.batchStatus.pelunasan_massal.oleh : ''}, `
                + `menutup ${this.batchStatus.pelunasan_massal.jumlah_diproses} tagihan. `
                + 'Terkunci sampai bulan depan supaya tagihan baru tidak ikut tersapu jadi lunas tanpa uang masuk. '
                + 'Untuk melunasi satu keluarga, pakai tombol "Set Lunas" pada barisnya.'
            );
        }

        const totalSisa = this.formatCurrency(this.summaryStats.totalRemaining);
        const result = await AppSwal.confirm(
            'Tutup buku: selesaikan SEMUA tunggakan?',
            `Seluruh tagihan aktif akan ditutup menjadi lunas penuh TANPA uang benar-benar masuk — sisa ${totalSisa} `
            + 'akan dicatat sebagai pelunasan otomatis berketerangan "Selesai sistem". '
            + 'Gunakan hanya saat tutup buku bulanan. Aksi ini hanya bisa sekali dalam bulan ini dan tidak bisa dibatalkan.',
            'Ya, tutup buku sekarang'
        );

        if (result.isConfirmed) {
            this.isLoading = true;
            try {
                const response = await fetch(
                    this.routes.lunasSemua, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json'
                        }
                    });
                const payload = await response.json();
                if (payload.status === 'success') {
                    this.refreshToTab();
                } else {
                    AppSwal.error(payload.message || 'Gagal memproses penyelesaian massal data.');
                }
            } catch (e) {
                AppSwal.error('Gagal memproses penyelesaian massal data.');
            } finally {
                this.isLoading = false;
            }
        }
    },

    exportExcel() {
        ButtonLoading.pulseCurrent();
        const params = new URLSearchParams({
            search: this.filterSearch,
            bulan: this.filterBulan,
            status: this.filterStatus
        });
        window.location.href =
            `${this.routes.export}?${params.toString()}`;
        AppSwal.toast('Sedang memproses kompilasi berkas Excel...', 'info');
    },

    openPaketModal() {
        this.resetPaketForm();
        this.showPaketModal = true;
    },

    resetPaketForm() {
        this.paketForm = {
            id: null,
            nama_paket: '',
            harga: '',
            pertemuan: 3
        };
    },

    async savePaket() {
        this.isLoading = true;
        const url = this.paketForm.id ?
            `${this.routes.paketBase}/${this.paketForm.id}` :
            this.routes.paketStore;
        const method = this.paketForm.id ? 'PUT' : 'POST';
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.paketForm)
            });
            if ((await response.json()).status === 'success') this.refreshToTab();
        } catch (e) {
            AppSwal.error('Gagal menyimpan aturan paket master.');
        } finally {
            this.isLoading = false;
        }
    },

    editPaket(p) {
        this.paketForm = {
            id: p.id,
            nama_paket: p.nama_paket,
            harga: p.harga,
            pertemuan: p.pertemuan
        };
    },

    async deletePaket(id) {
        const confirmation = await AppSwal.confirm('Hapus paket?', 'Paket pembayaran ini akan dihapus permanen.', 'Ya, hapus paket');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const response = await fetch(`${this.routes.paketBase}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                }
            });
            if ((await response.json()).status === 'success') this.refreshToTab();
        } catch (e) {
            AppSwal.error('Gagal menghapus komponen paket.');
        } finally {
            this.isLoading = false;
        }
    }
});
