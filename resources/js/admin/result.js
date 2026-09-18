import { kirim } from '../core/http.js';

export const resultHandler = ({ initialAspek, initialSiswa, routes }) => ({
    routes,
    aspekList: initialAspek || [],
    siswaList: initialSiswa || [],
    isLoading: false,

    form: { id: null, nama: '', indikator: '', aktif: true },
    formTerbuka: false,

    cariSiswa: '',
    urutSiswa: 'nama',
    hanyaSudahDinilai: false,

    raporSiswa: null,
    raporUntuk: null,
    isLoadingRapor: false,

    pertemuanDipilih: [],
    modeCetak: false,
    formCetak: { judul_sertifikat: '', kekuatan: '', perbaikan: '', komentar: '', rencana: '' },
    riwayatCetak: [],

    get aspekAktif() {
        return this.aspekList.filter(a => a.aktif);
    },

    get aspekNonaktif() {
        return this.aspekList.filter(a => !a.aktif);
    },

    get siapDinilai() {
        return this.aspekAktif.length > 0;
    },

    get daftarSiswa() {
        const kunci = this.cariSiswa.toLocaleLowerCase('id-ID').trim();

        return this.siswaList
            .filter(s => {
                if (this.hanyaSudahDinilai && s.total_pertemuan === 0) return false;
                if (kunci === '') return true;
                return [s.nama, s.panggilan, s.kelas]
                    .filter(Boolean)
                    .some(teks => String(teks).toLocaleLowerCase('id-ID').includes(kunci));
            })
            .sort((a, b) => {
                if (this.urutSiswa === 'nilai') {
                    return (b.rata_nilai ?? -1) - (a.rata_nilai ?? -1);
                }
                if (this.urutSiswa === 'pertemuan') {
                    return b.total_pertemuan - a.total_pertemuan;
                }
                return a.nama.localeCompare(b.nama);
            });
    },

    get statSiswa() {
        const dinilai = this.siswaList.filter(s => s.total_pertemuan > 0);
        const nilai = dinilai.map(s => s.rata_nilai).filter(n => n !== null && n !== undefined);

        return {
            total: this.siswaList.length,
            dinilai: dinilai.length,
            belum: this.siswaList.length - dinilai.length,
            rata: nilai.length ? (nilai.reduce((j, n) => j + n, 0) / nilai.length).toFixed(2) : null,
        };
    },

    warnaNilai(nilai) {
        if (nilai === null || nilai === undefined) return 'text-base-content/60';
        if (nilai >= 4) return 'text-success';
        if (nilai >= 3) return 'text-primary';
        if (nilai >= 2) return 'text-warning';
        return 'text-error';
    },

    bukaForm(aspek = null) {
        this.form = aspek
            ? { id: aspek.id, nama: aspek.nama, indikator: aspek.indikator, aktif: aspek.aktif }
            : { id: null, nama: '', indikator: '', aktif: true };
        this.formTerbuka = true;
    },

    tutupForm() {
        this.formTerbuka = false;
        this.form = { id: null, nama: '', indikator: '', aktif: true };
    },

    async simpanAspek() {
        if (!this.form.nama.trim() || !this.form.indikator.trim()) {
            return AppSwal.error('Nama aspek dan indikatornya sama-sama wajib diisi.');
        }

        this.isLoading = true;
        try {
            const url = this.form.id ? `${this.routes.aspekBase}/${this.form.id}` : this.routes.aspekStore;
            const res = await kirim(url, this.form.id ? 'PUT' : 'POST', this.form);

            if (res.status !== 'success') return AppSwal.error(res.message);

            this.aspekList = res.daftar;
            this.tutupForm();
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menyimpan aspek penilaian.');
        } finally {
            this.isLoading = false;
        }
    },

    async alihkanAktif(aspek) {
        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.aspekBase}/${aspek.id}`, 'PUT', {
                nama: aspek.nama,
                indikator: aspek.indikator,
                aktif: !aspek.aktif,
            });

            if (res.status !== 'success') return AppSwal.error(res.message);

            this.aspekList = res.daftar;
            AppSwal.toast(aspek.aktif ? 'Aspek dinonaktifkan.' : 'Aspek diaktifkan kembali.');
        } catch (e) {
            AppSwal.error('Gagal mengubah status aspek.');
        } finally {
            this.isLoading = false;
        }
    },

    async hapusAspek(aspek) {
        const konfirmasi = await AppSwal.confirm(
            `Hapus aspek "${aspek.nama}"?`,
            'Aspek yang sudah pernah dipakai menilai tidak bisa dihapus, hanya bisa dinonaktifkan.',
            'Ya, hapus'
        );
        if (!konfirmasi.isConfirmed) return;

        this.isLoading = true;
        try {
            const res = await kirim(`${this.routes.aspekBase}/${aspek.id}`, 'DELETE', {});

            if (res.status !== 'success') return AppSwal.error(res.message);

            this.aspekList = res.daftar;
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus aspek penilaian.');
        } finally {
            this.isLoading = false;
        }
    },

    async geser(aspek, arah) {
        const urut = [...this.aspekList].sort((a, b) => a.urutan - b.urutan || a.id - b.id);
        const posisi = urut.findIndex(a => a.id === aspek.id);
        const tujuan = posisi + arah;

        if (posisi === -1 || tujuan < 0 || tujuan >= urut.length) return;

        [urut[posisi], urut[tujuan]] = [urut[tujuan], urut[posisi]];

        this.isLoading = true;
        try {
            const res = await kirim(this.routes.aspekUrutan, 'PUT', { urutan: urut.map(a => a.id) });
            if (res.status !== 'success') return AppSwal.error(res.message);
            this.aspekList = res.daftar;
        } catch (e) {
            AppSwal.error('Gagal mengubah urutan aspek.');
        } finally {
            this.isLoading = false;
        }
    },

    async bukaRapor(siswa) {
        this.raporUntuk = siswa;
        this.raporSiswa = null;
        this.isLoadingRapor = true;

        try {
            const response = await fetch(`${this.routes.raporBase}/${siswa.id}/rapor`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Rapor gagal dimuat.');

            const hasil = await response.json();
            this.raporSiswa = hasil.data;
            this.riwayatCetak = hasil.riwayat_cetak || [];
            this.pertemuanDipilih = [];
            this.modeCetak = false;
            this.formCetak = {
                judul_sertifikat: '',
                kekuatan: hasil.catatan_terakhir?.kekuatan || '',
                perbaikan: hasil.catatan_terakhir?.perbaikan || '',
                komentar: hasil.catatan_terakhir?.komentar || '',
                rencana: hasil.catatan_terakhir?.rencana || '',
            };
        } catch (e) {
            AppSwal.error(e.message || 'Rapor gagal dimuat.');
            this.raporUntuk = null;
        } finally {
            this.isLoadingRapor = false;
        }
    },

    tutupRapor() {
        this.raporUntuk = null;
        this.raporSiswa = null;
        this.modeCetak = false;
        this.pertemuanDipilih = [];
        this.formCetak = { judul_sertifikat: '', kekuatan: '', perbaikan: '', komentar: '', rencana: '' };
        this.riwayatCetak = [];
    },

    get daftarPertemuan() {
        return this.raporSiswa?.daftar_pertemuan || [];
    },

    get semuaPertemuanDipilih() {
        return this.daftarPertemuan.length > 0
            && this.pertemuanDipilih.length === this.daftarPertemuan.length;
    },

    alihSemuaPertemuan() {
        this.pertemuanDipilih = this.semuaPertemuanDipilih
            ? []
            : this.daftarPertemuan.map(p => String(p.pertemuan_id));
    },

    pilihBulanIni() {
        const kini = new Date().toISOString().slice(0, 7);
        this.pertemuanDipilih = this.daftarPertemuan
            .filter(p => String(p.tanggal).slice(0, 7) === kini)
            .map(p => String(p.pertemuan_id));
    },

    bukaModeCetak() {
        this.modeCetak = true;
        if (this.pertemuanDipilih.length === 0) this.pilihBulanIni();
        if (this.pertemuanDipilih.length === 0) this.alihSemuaPertemuan();
    },

    cetak(jenis) {
        if (this.pertemuanDipilih.length === 0) {
            return AppSwal.error('Pilih dulu pertemuan mana yang mau dicetak.');
        }

        this.$refs.formCetak.action = jenis === 'sertifikat'
            ? `${this.routes.raporBase}/${this.raporUntuk.id}/sertifikat/cetak`
            : `${this.routes.raporBase}/${this.raporUntuk.id}/rapor/cetak`;
        this.$refs.formCetak.submit();
    },

    labelTren(tren) {
        return { naik: 'Naik', turun: 'Turun', stabil: 'Stabil' }[tren] || '-';
    },

    warnaTren(tren) {
        return {
            naik: 'text-success',
            turun: 'text-error',
            stabil: 'text-primary',
        }[tren] || 'text-base-content/60';
    },

    ikonTren(tren) {
        return {
            naik: 'fa-arrow-trend-up',
            turun: 'fa-arrow-trend-down',
            stabil: 'fa-arrows-left-right',
        }[tren] || 'fa-minus';
    },
});
