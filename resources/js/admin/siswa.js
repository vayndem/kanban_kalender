import { csrfToken } from '../core/http';

export const siswaHandler = ({
    initialSiswa,
    initialArsip,
    paketData,
    scheduleMetaData,
    hariData,
    sesiData,
    guruData,
    ruangData,
    routes,
}) => ({
    routes,
    allSiswas: initialSiswa || [],
    allArsips: initialArsip || [],
    pakets: paketData || [],
    scheduleMeta: scheduleMetaData || {},
    activeStudentSchedules: [],
    packageIndex: {},
    studentStatusIndex: {},
    allHaris: hariData || [],
    allSesis: sesiData || [],
    allGurus: guruData || [],
    allRuangs: ruangData || [],
    isLoadingJadwal: false,
    editJadwalRequestKey: 0,
    viewMode: 'aktif',
    showSiswaModal: false,
    siswaSearch: '',
    selectedSiswas: [],
    sortField: 'name',
    sortOrder: 'asc',
    siswaForm: {
        id: null,
        name: '',
        panggilan: '',
        kelas: '',
        no_hp: '',
        paket_pembayaran: ''
    },
    filterKelas: '',
    filterPaket: '',
    filterSesis: [],
    filterGurus: [],
    filterRuangs: [],

    init() {
        this.packageIndex = AppDomain.indexById(this.pakets);
        this.studentStatusIndex = AppDomain.buildStudentStatusIndex(
            this.allSiswas,
            this.scheduleMeta,
            this.packageIndex
        );
    },

    get kelasList() {
        return [...new Set(
            this.allSiswas
            .map(s => s.kelas)
            .filter(Boolean)
        )].sort();
    },

    get guruList() {
        return [...this.allGurus].sort((a, b) => a.name.localeCompare(b.name));
    },

    get ruangList() {
        return [...this.allRuangs].sort((a, b) => a.name.localeCompare(b.name));
    },

    get hasActiveFilter() {
        return this.filterKelas || this.filterPaket ||
            this.filterSesis.length > 0 || this.filterGurus.length > 0 ||
            this.filterRuangs.length > 0 || this.siswaSearch;
    },

    get filteredSiswa() {
        return AppDomain.filterStudents({
            students: this.allSiswas,
            archives: this.allArsips,
            mode: this.viewMode,
            search: this.siswaSearch,
            filters: {
                kelas: this.filterKelas,
                paket: this.filterPaket,
                sesiIds: this.filterSesis,
                guruIds: this.filterGurus,
                ruangIds: this.filterRuangs
            },
            scheduleMeta: this.scheduleMeta,
            sortField: this.sortField,
            sortOrder: this.sortOrder
        });
    },

    toggleSort(field) {
        if (this.sortField === field) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            this.sortOrder = 'asc';
        }
    },

    isAllSelected() {
        const currentList = this.filteredSiswa;
        if (currentList.length === 0) return false;
        return currentList.every(s => this.selectedSiswas.includes(s.id));
    },

    toggleSelectAll(checked) {
        const currentList = this.filteredSiswa;
        if (checked) {
            currentList.forEach(s => {
                if (!this.selectedSiswas.includes(s.id)) {
                    this.selectedSiswas.push(s.id);
                }
            });
        } else {
            currentList.forEach(s => {
                this.selectedSiswas = this.selectedSiswas.filter(id => id !== s.id);
            });
        }
    },

    resetFilter() {
        this.filterKelas = '';
        this.filterPaket = '';
        this.filterSesis = [];
        this.filterGurus = [];
        this.filterRuangs = [];
        this.siswaSearch = '';
    },

    getPaketName(id) {
        const p = this.packageIndex[Number(id)];
        return p ? p.nama_paket : 'N/A';
    },

    calculateScheduleStatus(siswa) {
        return AppDomain.calculateScheduleStatus(
            siswa,
            this.scheduleMeta,
            this.packageIndex
        );
    },

    getStatusJadwal(siswa) {
        return this.studentStatusIndex[Number(siswa.id)] || this.calculateScheduleStatus(siswa);
    },

    getSiswaJadwalList(siswaId) {
        if (!siswaId || Number(this.siswaForm.id) !== Number(siswaId)) return [];
        return this.activeStudentSchedules;
    },

    normalizeSchedules(schedules) {
        return AppDomain.normalizeStudentSchedules(
            schedules,
            this.allHaris,
            this.allSesis
        );
    },

    formatPhone() {
        let val = this.siswaForm.no_hp;
        if (!val) return;
        let digits = val.replace(/\D/g, '');
        if (digits.startsWith('0')) digits = '62' + digits.substring(1);
        if (digits.startsWith('8')) digits = '62' + digits;
        this.siswaForm.no_hp = '+' + digits;
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
        this.activeStudentSchedules = [];
    },

    async openEdit(siswa) {
        const requestKey = ++this.editJadwalRequestKey;
        this.siswaForm = {
            id: siswa.id,
            name: siswa.name || '',
            panggilan: siswa.panggilan || '',
            kelas: siswa.kelas || '',
            no_hp: siswa.no_hp || '',
            paket_pembayaran: siswa.paket_pembayaran == null ? '' : String(siswa.paket_pembayaran)
        };
        this.showSiswaModal = true;
        this.$nextTick(() => {
            this.$root.querySelectorAll('select').forEach(select =>
                select.dispatchEvent(new Event('searchable-select:sync'))
            );
        });
        this.isLoadingJadwal = true;

        try {
            const response = await fetch(`${this.routes.siswaBase}/${siswa.id}/jadwal`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Jadwal siswa gagal dimuat.');

            const result = await response.json();
            if (requestKey !== this.editJadwalRequestKey) return;
            this.activeStudentSchedules = this.normalizeSchedules(result.data);
        } catch (error) {
            if (requestKey !== this.editJadwalRequestKey) return;
            await AppSwal.error(error.message || 'Jadwal siswa gagal dimuat.');
        } finally {
            if (requestKey === this.editJadwalRequestKey) {
                this.isLoadingJadwal = false;
            }
        }
    },

    async simpanSiswa() {
        const isEdit = !!this.siswaForm.id;
        const url = isEdit ? `${this.routes.siswaBase}/${this.siswaForm.id}` :
            this.routes.siswaStore;
        const payload = {
            ...this.siswaForm,
            _token: csrfToken()
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
                await AppSwal.toast(res.message || 'Data siswa berhasil disimpan.');
                window.location.reload();
            } else {
                AppSwal.error(res.message);
            }
        } catch (e) {
            AppSwal.error('Sistem tidak dapat menyimpan data siswa.');
        }
    },

    async hapusSiswa(id) {
        const confirmation = await AppSwal.confirm('Arsipkan siswa?', 'Data siswa dan jadwal terkait akan dipindahkan ke arsip.', 'Ya, arsipkan');
        if (!confirmation.isConfirmed) return;
        try {
            const response = await fetch(`${this.routes.siswaBase}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                }
            });
            const res = await response.json();
            if (res.status === 'success') {
                await AppSwal.toast(res.message || 'Siswa berhasil diarsipkan.');
                window.location.reload();
            } else AppSwal.error(res.message);
        } catch (e) {
            AppSwal.error('Gagal mengarsipkan siswa.');
        }
    },

    async restoreSiswa(id) {
        const confirmation = await AppSwal.confirm('Pulihkan siswa?', 'Siswa akan dikembalikan ke daftar aktif.', 'Ya, pulihkan');
        if (!confirmation.isConfirmed) return;
        try {
            const response = await fetch(`${this.routes.arsipBase}/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'PUT'
                })
            });
            const res = await response.json();
            if (res.status === 'success') {
                await AppSwal.toast(res.message || 'Siswa berhasil dipulihkan.');
                window.location.reload();
            } else AppSwal.error(res.message);
        } catch (e) {
            AppSwal.error('Gagal memulihkan siswa.');
        }
    },

    async hapusPermanen(id) {
        const confirmation = await AppSwal.confirm('Hapus permanen?', 'Data ini tidak dapat dikembalikan setelah dihapus.', 'Ya, hapus permanen');
        if (!confirmation.isConfirmed) return;
        try {
            const response = await fetch(`${this.routes.arsipBase}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                }
            });
            const res = await response.json();
            if (res.status === 'success') {
                await AppSwal.toast(res.message || 'Data berhasil dihapus permanen.');
                window.location.reload();
            } else AppSwal.error(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus data arsip.');
        }
    },

    exportExcel() {
        ButtonLoading.pulseCurrent();
        const params = new URLSearchParams();
        if (this.filterKelas) params.set('kelas', this.filterKelas);
        if (this.filterPaket) params.set('paket_id', this.filterPaket);
        if (this.filterSesis.length) params.set('sesi_ids', this.filterSesis.join(','));
        if (this.filterGurus.length) params.set('guru_ids', this.filterGurus.join(','));
        if (this.filterRuangs.length) params.set('ruang_ids', this.filterRuangs.join(','));
        if (this.siswaSearch) params.set('search', this.siswaSearch);

        window.location.href = `/admin/siswa/export-excel?${params.toString()}`;
    }
});
