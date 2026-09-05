import { csrfToken, kirim } from '../core/http';

const HEADER_KOSONG = { tujuan_pembelajaran: '', kompetensi_awal: '', model_pembelajaran: '', sarana_media: '' };
const DETAIL_KOSONG = { materi: '', sub_materi: '', cara_mengajar: '', tugas: '', tujuan: '', hasil_akhir_pembelajaran: '', keterangan: '' };

export const modulAjarHandler = ({ isAdmin, initialKelasList, gurus, routes }) => ({
    isAdmin,
    kelasList: initialKelasList || [],
    gurus: gurus || [],
    routes,
    isLoading: false,
    selectedKelas: null,
    headerForm: { ...HEADER_KOSONG },
    detailForm: { ...DETAIL_KOSONG },
    editingDetailId: null,

    pengajaranDetail: null,
    pengajaranTahap: null,
    persiapanForm: { guru_pengganti_id: '' },
    nilaiForm: [],

    kelasDi(hariId, sesiId) {
        return this.kelasList.filter(k => Number(k.hari_id) === Number(hariId) && Number(k.sesi_id) === Number(sesiId));
    },

    adaSedangDipersiapkan(kelas) {
        return (kelas.modul_ajar?.details || []).some(d => d.sedang_dipersiapkan);
    },

    openKelas(kelas) {
        this.selectedKelas = kelas;
        this.headerForm = kelas.modul_ajar
            ? {
                tujuan_pembelajaran: kelas.modul_ajar.tujuan_pembelajaran || '',
                kompetensi_awal: kelas.modul_ajar.kompetensi_awal || '',
                model_pembelajaran: kelas.modul_ajar.model_pembelajaran || '',
                sarana_media: kelas.modul_ajar.sarana_media || '',
            }
            : { ...HEADER_KOSONG };
        this.resetDetailForm();
    },

    closeModal() {
        this.selectedKelas = null;
        this.tutupPengajaran();
    },

    get bisaUbahHeader() {
        return this.selectedKelas && (this.isAdmin || !this.selectedKelas.ada_header);
    },

    async simpanHeader() {
        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.headerBase}/${this.selectedKelas.kode_kelas}`, 'POST', this.headerForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.selectedKelas.modul_ajar = res.data;
            this.selectedKelas.ada_header = true;
            this.selectedKelas.jumlah_detail = (res.data.details || []).length;

            const idx = this.kelasList.findIndex(k => k.kode_kelas === this.selectedKelas.kode_kelas);
            if (idx !== -1) this.kelasList[idx] = { ...this.kelasList[idx], ...this.selectedKelas };

            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menyimpan modul ajar.');
        } finally {
            this.isLoading = false;
        }
    },

    resetDetailForm() {
        this.detailForm = { ...DETAIL_KOSONG };
        this.editingDetailId = null;
    },

    editDetail(detail) {
        this.editingDetailId = detail.id;
        this.detailForm = {
            materi: detail.materi || '',
            sub_materi: detail.sub_materi || '',
            cara_mengajar: detail.cara_mengajar || '',
            tugas: detail.tugas || '',
            tujuan: detail.tujuan || '',
            hasil_akhir_pembelajaran: detail.hasil_akhir_pembelajaran || '',
            keterangan: detail.keterangan || '',
        };
    },

    async simpanDetail() {
        this.isLoading = true;
        try {
            const isEdit = !!this.editingDetailId;
            const url = isEdit
                ? `${this.routes.detailBase}/${this.editingDetailId}`
                : `${this.routes.kelolaDetailBase}/${this.selectedKelas.modul_ajar.id}/detail`;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', this.detailForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (!this.selectedKelas.modul_ajar.details) this.selectedKelas.modul_ajar.details = [];

            if (isEdit) {
                const idx = this.selectedKelas.modul_ajar.details.findIndex(d => d.id === res.data.id);
                if (idx !== -1) this.selectedKelas.modul_ajar.details[idx] = res.data;
            } else {
                this.selectedKelas.modul_ajar.details.push(res.data);
                this.selectedKelas.jumlah_detail = this.selectedKelas.modul_ajar.details.length;
                const idx = this.kelasList.findIndex(k => k.kode_kelas === this.selectedKelas.kode_kelas);
                if (idx !== -1) this.kelasList[idx].jumlah_detail = this.selectedKelas.jumlah_detail;
            }

            AppSwal.toast(res.message);
            this.resetDetailForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan detail modul ajar.');
        } finally {
            this.isLoading = false;
        }
    },

    async hapusDetail(detail) {
        const confirmation = await AppSwal.confirm('Hapus detail ini?', `"${detail.materi}" akan dihapus permanen.`, 'Ya, hapus');
        if (!confirmation.isConfirmed) return;

        this.isLoading = true;
        try {
            const res = await fetch(`${this.routes.detailBase}/${detail.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            }).then(r => r.json());
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.selectedKelas.modul_ajar.details = this.selectedKelas.modul_ajar.details.filter(d => d.id !== detail.id);
            this.selectedKelas.jumlah_detail = this.selectedKelas.modul_ajar.details.length;
            const idx = this.kelasList.findIndex(k => k.kode_kelas === this.selectedKelas.kode_kelas);
            if (idx !== -1) this.kelasList[idx].jumlah_detail = this.selectedKelas.jumlah_detail;

            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus detail modul ajar.');
        } finally {
            this.isLoading = false;
        }
    },

    tutupPengajaran() {
        this.pengajaranDetail = null;
        this.pengajaranTahap = null;
        this.persiapanForm = { guru_pengganti_id: '' };
        this.nilaiForm = [];
    },

    bukaPersiapan(detail) {
        this.pengajaranDetail = detail;
        this.pengajaranTahap = 'persiapan';
        this.persiapanForm = { guru_pengganti_id: detail.guru_pengganti_id ? String(detail.guru_pengganti_id) : '' };
    },

    async simpanPersiapan() {
        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.detailBase}/${this.pengajaranDetail.id}/persiapan`, 'POST', this.persiapanForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            this.perbaruiDetailLokal(res.data);
            this.pengajaranDetail = { ...this.pengajaranDetail, ...res.data };
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal memulai persiapan.');
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
            const payload = { absensi: this.nilaiForm.map(({ siswa_id, hadir, nilai }) => ({ siswa_id, hadir, nilai: hadir ? (Number(nilai) || null) : null })) };
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
        const details = this.selectedKelas.modul_ajar.details || [];
        const idx = details.findIndex(d => d.id === detailBaru.id);
        if (idx !== -1) details[idx] = { ...details[idx], ...detailBaru };

        const idxKelas = this.kelasList.findIndex(k => k.kode_kelas === this.selectedKelas.kode_kelas);
        if (idxKelas !== -1) this.kelasList[idxKelas] = { ...this.kelasList[idxKelas], modul_ajar: this.selectedKelas.modul_ajar };
    },
});
