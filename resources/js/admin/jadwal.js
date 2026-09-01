import Sortable from 'sortablejs';

import { csrfToken } from '../core/http';

export const jadwalHandler = (data) => ({
    activeTab: data.activeTab || 'jadwal',
    universalSearch: '',
    showModal: false,
    showAddJadwalModal: false,
    editingJadwal: {},
    deletedTandaIds: [],
    allJadwals: data.jadwalsData || [],
    newJadwal: {
        hari_id: null,
        sesi_id: null,
        mata_pelajaran_id: null,
        guru_id: null,
        ruang_id: null,
        siswa_ids: []
    },
    allMapels: data.allMapels,
    allGurus: data.allGurus,
    allRuangs: data.allRuangs,
    allSiswas: data.allSiswas,
    allHaris: data.allHaris,
    allSesis: data.allSesis,
    searchIndex: data.searchIndex || { days: {}, sessions: {} },
    occupancy: data.occupancy || [],
    routes: data.routes,
    csrfToken: data.csrfToken,
    searchModalSiswa: '',
    showAddMenu: false,
    currentForm: '',
    selectedStudentDetail: null,
    formData: {},
    activeFormTab: 'input',
    formSearch: '',
    filteredFormList: [],
    availableStudentResults: [],
    selectedStudentResults: [],
    occupancyIndex: {},
    studentIndex: {},
    hariIndex: {},
    sesiIndex: {},
    scheduledStudentIds: new Set(),
    formSources: {},

    init() {
        this.studentIndex = AppDomain.indexById(this.allSiswas);
        this.hariIndex = AppDomain.indexById(this.allHaris);
        this.sesiIndex = AppDomain.indexById(this.allSesis);
        this.scheduledStudentIds = new Set(this.allJadwals.map(schedule => Number(schedule.siswa_id)));
        this.occupancyIndex = AppDomain.buildOccupancyIndex(this.occupancy);
        this.formSources = AppDomain.buildFormSources({
            subjects: this.allMapels,
            teachers: this.allGurus,
            rooms: this.allRuangs,
            sessions: this.allSesis,
            students: this.allSiswas
        });
        this.$watch('activeFormTab', tab => {
            if (tab === 'list') this.refreshFormList();
        });
        this.$watch('formSearch', () => this.refreshFormList());
        this.$watch('currentForm', () => this.refreshFormList());
        this.$watch('searchModalSiswa', () => this.refreshAvailableStudents());
    },

    refreshPage() {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', this.activeTab);
        window.location.href = url.toString();
    },

    matchesIndex(text) {
        const query = this.universalSearch.toLocaleLowerCase('id-ID').trim();
        return query === '' || String(text || '').includes(query);
    },

    dayMatches(dayId) {
        return this.matchesIndex(this.searchIndex.days[dayId]);
    },

    sessionMatches(sessionId) {
        return this.matchesIndex(this.searchIndex.sessions[sessionId]);
    },

    occupancyFor(target) {
        if (!target?.hari_id || !target?.sesi_id) return [];
        const key = `${Number(target.hari_id)}:${Number(target.sesi_id)}`;
        return (this.occupancyIndex[key] || []).filter(item => {
            const isOwnClass = target.old_hari_id &&
                Number(item.hari_id) === Number(target.old_hari_id) &&
                Number(item.sesi_id) === Number(target.old_sesi_id) &&
                Number(item.mapel_id) === Number(target.old_mapel_id) &&
                Number(item.guru_id) === Number(target.old_guru_id) &&
                Number(item.ruang_id) === Number(target.old_ruang_id);
            return !isOwnClass;
        });
    },

    availableGurus(target) {
        const occupiedIds = new Set(this.occupancyFor(target).map(item => Number(item.guru_id)));
        const allowCurrent = Boolean(target?.old_hari_id);
        return this.allGurus.filter(guru => !occupiedIds.has(Number(guru.id)) || (allowCurrent && Number(guru.id) === Number(target?.guru_id)));
    },

    availableRuangs(target) {
        const occupiedIds = new Set(this.occupancyFor(target).map(item => Number(item.ruang_id)));
        const allowCurrent = Boolean(target?.old_hari_id);
        return this.allRuangs.filter(ruang => !occupiedIds.has(Number(ruang.id)) || (allowCurrent && Number(ruang.id) === Number(target?.ruang_id)));
    },

    sudahPunyaJadwal(siswaId) {
        return this.scheduledStudentIds.has(Number(siswaId));
    },

    refreshFormList() {
        const search = this.formSearch.toLowerCase();
        const source = this.formSources[this.currentForm] || [];
        this.filteredFormList = search === '' ? source : source.filter(item => item.name
            .toLowerCase().includes(search));
    },

    formatSessionTime(sessionId) {
        const session = this.sesiIndex[Number(sessionId)];
        return session?.start_time
            ? `(${session.start_time.substring(0, 5)} - ${session.end_time.substring(0, 5)})`
            : '';
    },

    editDataItem(item) {
        this.activeFormTab = 'input';
        this.formData = JSON.parse(JSON.stringify(item));
        if (this.currentForm === 'tanda') {
            this.formData.keterangan = item.keterangan || item.name.split(' : ')[1];
        }
    },

    deleteDataItem(id) {
        if (!this.currentForm) {
            Swal.fire('Error', 'Tipe data tidak terdeteksi.', 'error');
            return;
        }
        Swal.fire({
            title: 'Hapus Data?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                let endpoint = this.routes[this.currentForm].destroy.replace(':id',
                    id);
                fetch(endpoint, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    })
                    .then(async response => {
                        const resData = await response.json();
                        if (!response.ok) throw resData;
                        return resData;
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Terhapus!', data.message, 'success')
                                .then(() => this.refreshPage());
                        }
                    })
                    .catch(error => {
                        Swal.fire('Gagal!', error.message ||
                            'Gagal menghubungi server.', 'error');
                    });
            }
        });
    },

    saveNewData() {
        const isEdit = this.formData.id ? true : false;
        let endpoint = isEdit ? this.routes[this.currentForm].update.replace(':id', this
            .formData.id) : this.routes[this.currentForm].store;
        let method = isEdit ? 'PUT' : 'POST';
        fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    ...this.formData,
                    _method: method
                })
            })
            .then(async response => {
                const result = await response.json();
                if (!response.ok) throw result;
                return result;
            })
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => this.refreshPage());
            })
            .catch(error => {
                let errorList = '';
                if (error.errors) {
                    errorList = '<ul class="text-left mt-2 list-disc list-inside">';
                    Object.values(error.errors).flat().forEach(msg => {
                        errorList += `<li class="text-sm">${msg}</li>`;
                    });
                    errorList += '</ul>';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    html: (error.message || 'Terjadi kesalahan') + errorList,
                    confirmButtonColor: '#3b82f6'
                });
            });
    },

    refreshStudentSelections() {
        const target = this.showModal ? this.editingJadwal : this.newJadwal;
        this.selectedStudentResults = (target.siswa_ids || [])
            .map(id => this.studentIndex[Number(id)])
            .filter(Boolean)
            .sort((a, b) => a.name.localeCompare(b.name));
        this.refreshAvailableStudents();
    },

    refreshAvailableStudents() {
        const search = this.searchModalSiswa.toLowerCase().trim();
        const selectedIds = this.showModal ? this.editingJadwal.siswa_ids : this.newJadwal
            .siswa_ids;
        if (search === '') {
            this.availableStudentResults = [];
            return;
        }
        const target = this.showModal ? this.editingJadwal : this.newJadwal;
        const occupiedStudentIds = new Set(this.occupancyFor(target).map(item => Number(item.siswa_id)));
        const selectedStudentIds = new Set((selectedIds || []).map(Number));
        this.availableStudentResults = this.allSiswas.filter(s => {
            const isSelected = selectedStudentIds.has(Number(s.id));
            const matchesSearch = s.name.toLowerCase().includes(search) || (s
                .panggilan && s.panggilan.toLowerCase().includes(search));
            return !isSelected && !occupiedStudentIds.has(Number(s.id)) && matchesSearch;
        }).sort((a, b) => a.name.localeCompare(b.name)).slice(0, 10);
    },

    addSiswa(id) {
        const target = this.showModal ? this.editingJadwal : this.newJadwal;
        if (!target.siswa_ids.includes(id)) target.siswa_ids.push(id);
        this.searchModalSiswa = '';
        this.refreshStudentSelections();
    },

    removeSiswa(id) {
        const target = this.showModal ? this.editingJadwal : this.newJadwal;
        target.siswa_ids = target.siswa_ids.filter(sid => sid !== id);
        if (this.selectedStudentDetail && this.selectedStudentDetail.id === id) this
            .selectedStudentDetail = null;
        this.refreshStudentSelections();
    },

    hasTanda(siswa) {
        return siswa.tandas && siswa.tandas.length > 0;
    },

    viewStudentDetail(siswa) {
        this.selectedStudentDetail = siswa;
    },

    async markTandaForDeletion(tandaId, studentId) {
        const confirmation = await AppSwal.confirm('Hapus catatan?', 'Catatan akan dihapus saat perubahan jadwal disimpan.', 'Ya, tandai hapus');
        if (!confirmation.isConfirmed) return;
        this.deletedTandaIds.push(tandaId);
        this.selectedStudentDetail.tandas = this.selectedStudentDetail.tandas.filter(t => t
            .id !== tandaId);
        const studentIndex = this.allSiswas.findIndex(s => s.id === studentId);
        if (studentIndex !== -1) {
            this.allSiswas[studentIndex].tandas = this.allSiswas[studentIndex].tandas
                .filter(t => t.id !== tandaId);
        }
    },

    saveJadwal() {
        const payload = {
            ...this.editingJadwal,
            deleted_tanda_ids: this.deletedTandaIds
        };
        fetch(this.routes.jadwal.updateKelas, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(async r => {
                const res = await r.json();
                if (!r.ok) throw res;
                return res;
            })
            .then(data => {
                if (data.status === 'success') {
                    this.showModal = false;
                    this.deletedTandaIds = [];
                    window.location.reload();
                }
            })
            .catch(error => {
                Swal.fire('Gagal!', error.message || 'Gagal menyimpan.', 'error');
            });
    },

    openAddJadwalModal(hariId, sesiId) {
        this.newJadwal = {
            hari_id: hariId,
            sesi_id: sesiId,
            mata_pelajaran_id: this.allMapels.length > 0 ? this.allMapels[0].id : null,
            guru_id: null,
            ruang_id: null,
            siswa_ids: []
        };
        this.newJadwal.guru_id = this.availableGurus(this.newJadwal)[0]?.id || null;
        this.newJadwal.ruang_id = this.availableRuangs(this.newJadwal)[0]?.id || null;
        this.searchModalSiswa = '';
        this.showAddJadwalModal = true;
        this.selectedStudentDetail = null;
        this.refreshStudentSelections();
    },

    saveNewJadwal() {
        fetch(this.routes.jadwal.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(this.newJadwal)
            })
            .then(async r => {
                const res = await r.json();
                if (!r.ok) throw res;
                return res;
            })
            .then(data => {
                if (data.status === 'success') window.location.reload();
            })
            .catch(error => {
                Swal.fire('Gagal!', error.message || 'Gagal menyimpan.', 'error');
            });
    },

    openStashOptions() {
        Swal.fire({
            title: 'Stash Manager',
            text: 'Backup atau Restore data jadwal, perubahan jadwal bersifat permanen dan tidak bisa dibatalkan!',
            icon: 'info',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="fas fa-cloud-download-alt mr-2"></i> Download Stash',
            denyButtonText: '<i class="fas fa-cloud-upload-alt mr-2"></i> Upload Stash',
            confirmButtonColor: '#059669',
            denyButtonColor: '#3b82f6',
            background: document.documentElement.classList.contains('dark') ?
                '#1f2937' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#fff' :
                '#000',
        }).then((result) => {
            if (result.isConfirmed) {
                this.downloadStash();
            } else if (result.isDenied) {
                this.uploadStash();
            }
        });
    },

    downloadStash() {
        ButtonLoading.pulseCurrent();
        window.location.href = this.routes.jadwal.downloadStash;
    },

    async uploadStash() {
        const {
            value: file
        } = await Swal.fire({
            title: 'Upload & Replace Jadwal',
            text: 'PERINGATAN: Seluruh jadwal saat ini akan dihapus dan diganti dengan isi file ini!',
            input: 'file',
            inputAttributes: {
                'accept': '.stash',
                'aria-label': 'Pilih file stash'
            },
            showCancelButton: true,
            confirmButtonText: 'PROSES REPLACE',
            confirmButtonColor: '#d33',
        });

        if (file) {
            Swal.showLoading();
            let formData = new FormData();
            formData.append('file_stash', file);
            formData.append('_token', this.csrfToken);

            try {
                const response = await fetch(
                    this.routes.jadwal.uploadStash, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                const res = await response.json();

                if (res.status === 'success') {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => window
                        .location.reload());
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
            }
        }
    },

    openExportOptions() {
        const searchTerm = this.universalSearch.trim();
        let htmlContent = searchTerm ?
            `<div class='text-left'><p class='text-gray-600 mb-2'>Pencarian aktif:</p><div class='bg-blue-50 p-3 rounded border border-blue-200 text-blue-800 text-lg font-bold text-center'>'${searchTerm}'</div></div>` :
            `<div class='text-left'><div class='bg-yellow-50 p-3 rounded border border-yellow-200 text-yellow-800'>Semua data akan diproses.</div></div>`;
        Swal.fire({
            title: 'Export Opsi',
            html: htmlContent,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="fas fa-file-excel"></i> Excel',
            denyButtonText: '<i class="fas fa-copy"></i> Copy WA',
            confirmButtonColor: '#d33',
            denyButtonColor: '#3b82f6'
        }).then((result) => {
            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (result.isConfirmed) {
                ButtonLoading.pulseCurrent();
                window.open(this.routes.jadwal.export+'?' + params.toString(),
                    '_blank');
            } else if (result.isDenied) {
                Swal.showLoading();
                fetch(this.routes.jadwal.generateText + '?' + params.toString())
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            navigator.clipboard.writeText(data.text).then(
                                () => {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Disalin!',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                });
                        }
                    });
            }
        });
    }
});

export function installJadwalDragDrop() {
    document.addEventListener('DOMContentLoaded', function() {
        const board = document.querySelector('[data-update-posisi-url]');
        if (!board) return;

        const updatePosisiUrl = board.dataset.updatePosisiUrl;

        document.querySelectorAll('.kanban-slot').forEach(slot => {
            new Sortable(slot, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    const toSlot = evt.to;
                    const fromSlot = evt.from;
                    const card = evt.item;
                    if (!toSlot || !toSlot.dataset.sesiId || !toSlot.dataset.hariId) return;
                    fetch(updatePosisiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken()
                            },
                            body: JSON.stringify({
                                mapel_id: card.dataset.mapelId,
                                guru_id: card.dataset.guruId,
                                ruang_id: card.dataset.ruangId,
                                old_hari_id: fromSlot.dataset.hariId,
                                old_sesi_id: fromSlot.dataset.sesiId,
                                new_hari_id: toSlot.dataset.hariId,
                                new_sesi_id: toSlot.dataset.sesiId,
                            })
                        })
                        .then(async r => {
                            const res = await r.json();
                            if (!r.ok) throw res;
                            return res;
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                card.dataset.hariId = toSlot.dataset.hariId;
                                card.dataset.sesiId = toSlot.dataset.sesiId;
                            } else {
                                fromSlot.appendChild(card);
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            fromSlot.appendChild(card);
                            Swal.fire('Gagal!', error.message ||
                                'Gagal memindahkan jadwal.', 'error');
                        });
                }
            });
        });
    });
}
