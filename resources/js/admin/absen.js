import { kirim } from '../core/http';

const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export const absenHandler = ({ isAdmin, guruId, initialKelasList, allHaris, routes }) => ({
    isAdmin,
    guruId: guruId ? Number(guruId) : null,
    kelasList: initialKelasList || [],
    allHaris: allHaris || [],
    routes,
    isLoading: false,
    selectedKelas: null,
    activeDayMobile: null,

    pengajaranDetail: null,
    pengajaranTahap: null,
    nilaiForm: [],

    init() {
        const todayName = DAY_NAMES[new Date().getDay()];
        const todayHari = this.allHaris.find(h => h.name === todayName);
        this.activeDayMobile = todayHari ? todayHari.id : (this.allHaris[0]?.id ?? null);
    },

    kelasDi(hariId, sesiId) {
        return this.kelasList.filter(k => Number(k.hari_id) === Number(hariId) && Number(k.sesi_id) === Number(sesiId));
    },

    adaSedangDipersiapkan(kelas) {
        return (kelas.modul_ajar?.details || []).some(d => d.sedang_dipersiapkan);
    },

    adaSlotTerbuka(kelas) {
        return (kelas.modul_ajar?.details || []).some(d => d.tidak_bisa_hadir);
    },

    isPemilikKelas(kelas) {
        return this.guruId !== null && Number(kelas.guru_id) === this.guruId;
    },

    openKelas(kelas) {
        this.selectedKelas = kelas;
    },

    closeModal() {
        this.selectedKelas = null;
        this.tutupPengajaran();
    },

    tutupPengajaran() {
        this.pengajaranDetail = null;
        this.pengajaranTahap = null;
        this.nilaiForm = [];
    },

    async mulaiAjar(detail) {
        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.detailBase}/${detail.id}/persiapan`, 'POST', {});
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.perbaruiDetailLokal(res.data);
            this.pengajaranDetail = res.data;
            this.pengajaranTahap = 'persiapan';
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal memulai persiapan.');
        } finally {
            this.isLoading = false;
        }
    },

    async tandaiTidakBisaHadir(detail) {
        const confirmation = await AppSwal.confirm(
            'Tandai tidak bisa hadir?',
            'Kelas ini akan langsung terbuka untuk semua guru — siapa cepat, dia yang dapat.',
            'Ya, tandai'
        );
        if (!confirmation.isConfirmed) return;

        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.detailBase}/${detail.id}/tidak-bisa-hadir`, 'POST', {});
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.perbaruiDetailLokal(res.data);
            AppSwal.toast(res.message);
            this.closeModal();
        } catch (e) {
            AppSwal.error('Gagal menandai tidak bisa hadir.');
        } finally {
            this.isLoading = false;
        }
    },

    async ambilKelas(detail) {
        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.detailBase}/${detail.id}/klaim`, 'POST', {});
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.perbaruiDetailLokal(res.data);
            this.pengajaranDetail = res.data;
            this.pengajaranTahap = 'persiapan';
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal mengambil kelas ini.');
        } finally {
            this.isLoading = false;
        }
    },

    bukaNilai(detail) {
        this.pengajaranDetail = detail;
        this.pengajaranTahap = 'nilai';
        this.nilaiForm = (this.selectedKelas.siswa_list || []).map(s => {
            const lama = (detail.absensis || []).find(a => Number(a.siswa_id) === Number(s.id));
            return {
                siswa_id: s.id,
                nama: s.panggilan || s.name,
                hadir: lama ? lama.hadir : true,
                nilai: lama ? lama.nilai : null,
            };
        });
    },

    async simpanNilai() {
        this.isLoading = true;
        try {
            const payload = {
                absensi: this.nilaiForm.map(({ siswa_id, hadir, nilai }) => ({
                    siswa_id,
                    hadir,
                    nilai: hadir ? (Number(nilai) || null) : null,
                })),
            };
            const res = await kirim(`${this.routes.detailBase}/${this.pengajaranDetail.id}/nilai`, 'POST', payload);
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.perbaruiDetailLokal(res.data);
            AppSwal.toast(res.message);
            this.tutupPengajaran();
        } catch (e) {
            AppSwal.error('Gagal menyimpan nilai.');
        } finally {
            this.isLoading = false;
        }
    },

    perbaruiDetailLokal(detailBaru) {
        if (!this.selectedKelas?.modul_ajar) return;
        const details = this.selectedKelas.modul_ajar.details || [];
        const idx = details.findIndex(d => d.id === detailBaru.id);
        if (idx !== -1) details[idx] = { ...details[idx], ...detailBaru };

        const idxKelas = this.kelasList.findIndex(k => k.kode_kelas === this.selectedKelas.kode_kelas);
        if (idxKelas !== -1) this.kelasList[idxKelas] = { ...this.kelasList[idxKelas], modul_ajar: this.selectedKelas.modul_ajar };
    },
});
