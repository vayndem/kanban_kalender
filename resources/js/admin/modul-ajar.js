import { csrfToken, kirim } from '../core/http';

const HEADER_KOSONG = { tujuan_pembelajaran: '', kompetensi_awal: '', model_pembelajaran: '', sarana_media: '' };
const DETAIL_KOSONG = { materi: '', sub_materi: '', cara_mengajar: '', tugas: '', tujuan: '', hasil_akhir_pembelajaran: '', keterangan: '' };

export const modulAjarHandler = ({ isAdmin, initialKelasList, routes }) => ({
    isAdmin,
    kelasList: initialKelasList || [],
    routes,
    isLoading: false,
    selectedKelas: null,
    headerForm: { ...HEADER_KOSONG },
    detailForm: { ...DETAIL_KOSONG },
    editingDetailId: null,

    kelasDi(hariId, sesiId) {
        return this.kelasList.filter(k => Number(k.hari_id) === Number(hariId) && Number(k.sesi_id) === Number(sesiId));
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

});
