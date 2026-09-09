import { kirim } from '../core/http';

const rupiah = (nilai) => 'Rp ' + Number(nilai || 0).toLocaleString('id-ID');

export const payrollHandler = ({ initialRingkasan, initialRiwayat, routes }) => ({
    routes,
    ringkasan: initialRingkasan || [],
    riwayat: initialRiwayat || [],
    isLoading: false,
    cari: '',
    editId: null,
    formTarif: { gaji_bawaan: 0, gaji_per_kehadiran: 0 },
    strukTerbuka: null,
    isLoadingStruk: false,
    strukRequestKey: 0,

    rupiah,

    get daftar() {
        const kata = this.cari.trim().toLowerCase();
        if (!kata) return this.ringkasan;
        return this.ringkasan.filter((g) => g.nama.toLowerCase().includes(kata));
    },

    get totalPerkiraan() {
        return this.daftar.reduce((jumlah, g) => jumlah + Number(g.perkiraan_total || 0), 0);
    },

    get adaKehadiranTertunda() {
        return this.ringkasan.some((g) => g.kehadiran_belum_dibayar > 0);
    },

    bukaEdit(guru) {
        this.editId = guru.id;
        this.formTarif = {
            gaji_bawaan: guru.gaji_bawaan,
            gaji_per_kehadiran: guru.gaji_per_kehadiran,
        };
    },

    batalEdit() {
        this.editId = null;
    },

    async simpanTarif(guru) {
        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.tarifBase}/${guru.id}/tarif`, 'PUT', this.formTarif);
            if (res.status === 'success') {
                this.ringkasan = res.data;
                this.editId = null;
                AppSwal.toast(res.message);
            } else {
                AppSwal.error(res.message);
            }
        } finally {
            this.isLoading = false;
        }
    },

    async jalankan(guru) {
        const konfirmasi = await AppSwal.confirm(
            `Terbitkan struk untuk ${guru.nama}?`,
            `Total ${rupiah(guru.perkiraan_total)} dari ${guru.kehadiran_belum_dibayar} kehadiran. Setelah ini hitungan kehadirannya kembali ke nol.`,
            'Ya, terbitkan'
        );
        if (!konfirmasi.isConfirmed) return;

        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.jalankanBase}/${guru.id}/jalankan`, 'POST', {});
            if (res.status === 'success') {
                this.ringkasan = res.data;
                this.riwayat = res.riwayat;
                AppSwal.toast(res.message);
            } else {
                AppSwal.error(res.message);
            }
        } finally {
            this.isLoading = false;
        }
    },

    async jalankanSemua() {
        const konfirmasi = await AppSwal.confirm(
            'Terbitkan struk untuk semua guru?',
            `Perkiraan total ${rupiah(this.totalPerkiraan)}. Semua hitungan kehadiran akan kembali ke nol.`,
            'Ya, jalankan semua'
        );
        if (!konfirmasi.isConfirmed) return;

        this.isLoading = true;
        try {
            const res = await kirim(this.routes.jalankanSemua, 'POST', {});
            if (res.status === 'success') {
                this.ringkasan = res.data;
                this.riwayat = res.riwayat;
                AppSwal.toast(res.message);
                if (res.dilewati && res.dilewati.length) {
                    AppSwal.error('Dilewati:\n' + res.dilewati.join('\n'));
                }
            } else {
                AppSwal.error(res.message);
            }
        } finally {
            this.isLoading = false;
        }
    },

    async bukaStruk(id) {
        const requestKey = ++this.strukRequestKey;
        this.strukTerbuka = null;
        this.isLoadingStruk = true;

        try {
            const respon = await fetch(`${this.routes.strukBase}/${id}`, {
                headers: { Accept: 'application/json' },
            });
            if (!respon.ok) throw new Error('Struk penggajian gagal dimuat.');

            const hasil = await respon.json();
            if (requestKey !== this.strukRequestKey) return;
            this.strukTerbuka = hasil.data;
        } catch (error) {
            if (requestKey !== this.strukRequestKey) return;
            AppSwal.error(error.message || 'Struk penggajian gagal dimuat.');
        } finally {
            if (requestKey === this.strukRequestKey) this.isLoadingStruk = false;
        }
    },

    tutupStruk() {
        this.strukRequestKey++;
        this.strukTerbuka = null;
    },

    async batalkanStruk(struk) {
        const { value: alasan } = await window.Swal.fire({
            title: 'Batalkan struk ini?',
            text: 'Kehadirannya akan dikembalikan ke hitungan berjalan supaya bisa digaji ulang.',
            input: 'text',
            inputPlaceholder: 'Alasan pembatalan',
            showCancelButton: true,
            confirmButtonText: 'Ya, batalkan',
            cancelButtonText: 'Tutup',
            inputValidator: (nilai) => (!nilai ? 'Alasan pembatalan wajib diisi.' : undefined),
        });
        if (!alasan) return;

        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.strukBase}/${struk.id}/batalkan`, 'POST', { alasan_batal: alasan });
            if (res.status === 'success') {
                this.ringkasan = res.data;
                this.riwayat = res.riwayat;
                this.tutupStruk();
                AppSwal.toast(res.message);
            } else {
                AppSwal.error(res.message);
            }
        } finally {
            this.isLoading = false;
        }
    },
});
