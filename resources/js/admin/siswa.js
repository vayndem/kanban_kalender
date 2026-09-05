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
    showDetailModal: false,
    detailSiswa: {},
    catatanForm: { keterangan: '' },
    isSavingCatatan: false,
    siswaSearch: '',
    selectedSiswas: [],
    sortField: 'name',
    sortOrder: 'asc',
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
        if (!siswaId || Number(this.detailSiswa.id) !== Number(siswaId)) return [];
        return this.activeStudentSchedules;
    },

    normalizeSchedules(schedules) {
        return AppDomain.normalizeStudentSchedules(
            schedules,
            this.allHaris,
            this.allSesis
        );
    },

    async openDetail(siswa) {
        const requestKey = ++this.editJadwalRequestKey;
        this.detailSiswa = siswa;
        this.catatanForm = { keterangan: '' };
        this.showDetailModal = true;
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

    async simpanCatatan() {
        if (!this.detailSiswa.id || !this.catatanForm.keterangan.trim()) return;

        this.isSavingCatatan = true;
        try {
            const response = await fetch(this.routes.tandaStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    siswa_id: this.detailSiswa.id,
                    keterangan: this.catatanForm.keterangan
                })
            });
            const res = await response.json();
            if (res.status === 'success') {
                window.location.reload();
            } else {
                AppSwal.error(res.message || 'Gagal menyimpan catatan.');
            }
        } catch (e) {
            AppSwal.error('Gagal menyimpan catatan.');
        } finally {
            this.isSavingCatatan = false;
        }
    },

    async hapusCatatan(id) {
        const confirmation = await AppSwal.confirm('Hapus catatan?', 'Catatan ini akan dihapus permanen.', 'Ya, hapus');
        if (!confirmation.isConfirmed) return;

        this.isSavingCatatan = true;
        try {
            const response = await fetch(`${this.routes.tandaBase}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                }
            });
            const res = await response.json();
            if (res.status === 'success') {
                window.location.reload();
            } else {
                AppSwal.error(res.message || 'Gagal menghapus catatan.');
            }
        } catch (e) {
            AppSwal.error('Gagal menghapus catatan.');
        } finally {
            this.isSavingCatatan = false;
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
