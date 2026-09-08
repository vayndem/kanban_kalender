import { csrfToken, kirim } from '../core/http';

function normalizePhone(value) {
    if (!value) return '';
    let digits = String(value).replace(/\D/g, '');
    if (digits.startsWith('0')) digits = '62' + digits.substring(1);
    if (digits.startsWith('8')) digits = '62' + digits;
    return '+' + digits;
}

function normalizeName(value) {
    return String(value || '').trim().toLowerCase();
}

const JEDA_MINIMAL_SESI_MENIT = 30;
const JAM_ISTIRAHAT = { mulai: '11:45', selesai: '12:45' };
const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function jamKeMenit(jam) {
    const [h, m] = String(jam || '').split(':').map(Number);
    return (h || 0) * 60 + (m || 0);
}

function rentangTumpangTindih(mulaiA, selesaiA, mulaiB, selesaiB) {
    return mulaiA < selesaiB && mulaiB < selesaiA;
}

export const workshopHandler = ({
    initialMapels,
    initialGurus,
    initialRuangs,
    initialSesis,
    initialPakets,
    initialKemampuans,
    initialKetersediaan,
    initialSiswas,
    petaKelas,
    editSiswaId,
    routes,
}) => ({
    routes,
    petaKelas: petaKelas || {},
    activeSection: 'siswa',
    isLoading: false,

    mapels: initialMapels || [],
    gurus: initialGurus || [],
    ruangs: initialRuangs || [],
    sesis: initialSesis || [],
    pakets: initialPakets || [],
    kemampuans: initialKemampuans || [],
    ketersediaan: initialKetersediaan || [],
    siswas: initialSiswas || [],

    searchMapel: '',
    searchGuru: '',
    searchRuang: '',
    searchSesi: '',
    searchSiswa: '',
    searchKetersediaan: '',
    activeDayMobile: DAY_NAMES[new Date().getDay()],

    mapelForm: { id: null, name: '' },
    guruForm: { id: null, name: '' },
    ruangForm: { id: null, name: '' },
    sesiForm: { id: null, name: '', start_time: '', end_time: '' },
    kemampuanForm: { id: null, keterangan: '' },
    siswaForm: { id: null, name: '', panggilan: '', kelas: '', no_hp: '', paket_pembayaran: '', tingkat_kemampuan_id: '' },

    init() {
        if (editSiswaId) {
            const target = this.siswas.find(s => Number(s.id) === Number(editSiswaId));
            if (target) {
                this.activeSection = 'siswa';
                this.editSiswa(target);
                this.$nextTick(() => {
                    document.getElementById('workshop-siswa-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        }

        if (this.ketersediaan.length && !this.ketersediaan.some(slot => slot.hari === this.activeDayMobile)) {
            this.activeDayMobile = this.ketersediaan[0].hari;
        }
    },

    cariKemiripan(list, namaDiketik, idSaatIni) {
        const nama = normalizeName(namaDiketik);
        if (nama.length < 3) return [];

        return list.filter(item => {
            if (idSaatIni && Number(item.id) === Number(idSaatIni)) return false;
            const namaItem = normalizeName(item.name);
            return namaItem === nama || namaItem.includes(nama) || nama.includes(namaItem);
        }).slice(0, 5);
    },

    get kemiripanMapel() {
        return this.cariKemiripan(this.mapels, this.mapelForm.name, this.mapelForm.id);
    },
    get kemiripanGuru() {
        return this.cariKemiripan(this.gurus, this.guruForm.name, this.guruForm.id);
    },
    get kemiripanRuang() {
        return this.cariKemiripan(this.ruangs, this.ruangForm.name, this.ruangForm.id);
    },
    get kemiripanSesi() {
        return this.cariKemiripan(this.sesis, this.sesiForm.name, this.sesiForm.id);
    },

    get konflikJamSesi() {
        const mulai = jamKeMenit(this.sesiForm.start_time);
        const selesai = jamKeMenit(this.sesiForm.end_time);
        if (!this.sesiForm.start_time || !this.sesiForm.end_time || selesai <= mulai) return [];

        const pesan = [];

        const istirahatMulai = jamKeMenit(JAM_ISTIRAHAT.mulai);
        const istirahatSelesai = jamKeMenit(JAM_ISTIRAHAT.selesai);
        if (rentangTumpangTindih(mulai, selesai, istirahatMulai, istirahatSelesai)) {
            pesan.push(`Jam ini bentrok dengan jam istirahat (${JAM_ISTIRAHAT.mulai}-${JAM_ISTIRAHAT.selesai}).`);
        }

        for (const s of this.sesis) {
            if (Number(s.id) === Number(this.sesiForm.id)) continue;
            const jamMulaiLain = String(s.start_time || '').substring(0, 5);
            const jamSelesaiLain = String(s.end_time || '').substring(0, 5);
            const sMulai = jamKeMenit(jamMulaiLain);
            const sSelesai = jamKeMenit(jamSelesaiLain);

            if (rentangTumpangTindih(mulai, selesai, sMulai, sSelesai)) {
                pesan.push(`Jam ini bentrok langsung dengan Sesi "${s.name}" (${jamMulaiLain}-${jamSelesaiLain}).`);
                continue;
            }

            const jeda = mulai >= sSelesai ? mulai - sSelesai : (selesai <= sMulai ? sMulai - selesai : null);
            if (jeda !== null && jeda < JEDA_MINIMAL_SESI_MENIT) {
                pesan.push(`Jeda ke Sesi "${s.name}" (${jamMulaiLain}-${jamSelesaiLain}) cuma ${jeda} menit — sebaiknya minimal ${JEDA_MINIMAL_SESI_MENIT} menit.`);
            }
        }

        return pesan;
    },
    get kemiripanSiswaNama() {
        return this.cariKemiripan(this.siswas, this.siswaForm.name, this.siswaForm.id);
    },

    get kemiripanNoHp() {
        const nomor = normalizePhone(this.siswaForm.no_hp);
        if (nomor.length < 6) return [];

        return this.siswas.filter(s =>
            Number(s.id) !== Number(this.siswaForm.id) &&
            s.no_hp && normalizePhone(s.no_hp) === nomor
        );
    },

    get infoKelas() {
        const kelas = String(this.siswaForm.kelas || '').trim();
        if (!kelas) return [];

        const kunciCocok = Object.keys(this.petaKelas).find(
            k => k.trim().toLowerCase() === kelas.toLowerCase()
        );
        return kunciCocok ? this.petaKelas[kunciCocok] : [];
    },

    formatPhone() {
        this.siswaForm.no_hp = this.siswaForm.no_hp ? normalizePhone(this.siswaForm.no_hp) : '';
    },

    filterList(list, search, fields) {
        const q = search.trim().toLowerCase();
        if (!q) return list;
        return list.filter(item => fields.some(f => String(item[f] || '').toLowerCase().includes(q)));
    },

    get filteredMapels() {
        return this.filterList(this.mapels, this.searchMapel, ['name']);
    },
    get filteredGurus() {
        return this.filterList(this.gurus, this.searchGuru, ['name']);
    },
    get filteredRuangs() {
        return this.filterList(this.ruangs, this.searchRuang, ['name']);
    },
    get filteredSesis() {
        return this.filterList(this.sesis, this.searchSesi, ['name']);
    },
    get filteredSiswas() {
        return this.filterList(this.siswas, this.searchSiswa, ['name', 'panggilan', 'kelas', 'no_hp']);
    },

    get ketersediaanGrid() {
        const q = this.searchKetersediaan.trim().toLowerCase();
        const toChips = (names) => names.map(name => ({ name, match: q.length > 0 && name.toLowerCase().includes(q) }));

        const hariOrder = [];
        const bySesi = {};
        for (const slot of this.ketersediaan) {
            if (!hariOrder.includes(slot.hari)) hariOrder.push(slot.hari);
            if (!bySesi[slot.sesi]) bySesi[slot.sesi] = {};
            bySesi[slot.sesi][slot.hari] = {
                ...slot,
                ruang_kosong: toChips(slot.ruang_kosong),
                guru_kosong: toChips(slot.guru_kosong),
            };
        }

        const rows = Object.entries(bySesi).map(([sesi, byHari]) => ({ sesi, byHari }));

        return { hariOrder, rows };
    },

    getPaketName(id) {
        const p = this.pakets.find(x => Number(x.id) === Number(id));
        return p ? p.nama_paket : '-';
    },

    formatPaketLabel(p) {
        const harga = 'Rp ' + Number(p.harga || 0).toLocaleString('id-ID');
        return `${p.nama_paket} — ${harga} · ${p.pertemuan}x pertemuan`;
    },

    resetMapelForm() {
        this.mapelForm = { id: null, name: '' };
    },
    editMapel(m) {
        this.mapelForm = { id: m.id, name: m.name };
    },
    async simpanMapel() {
        this.isLoading = true;
        try {
            const isEdit = !!this.mapelForm.id;
            const url = isEdit ? `${this.routes.mapelBase}/${this.mapelForm.id}` : this.routes.mapelStore;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', this.mapelForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (isEdit) {
                const idx = this.mapels.findIndex(m => m.id === res.data.id);
                if (idx !== -1) this.mapels[idx] = { ...this.mapels[idx], name: res.data.name };
            } else {
                this.mapels.push({ id: res.data.id, name: res.data.name, jumlah_baris_jadwal: 0, bisa_dihapus: true });
            }
            AppSwal.toast(res.message);
            this.resetMapelForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan mata pelajaran.');
        } finally {
            this.isLoading = false;
        }
    },
    async hapusMapel(m) {
        const confirmation = await AppSwal.confirm('Hapus mata pelajaran?', `"${m.name}" akan dihapus permanen.`, 'Ya, hapus');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const res = await fetch(`${this.routes.mapelBase}/${m.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            }).then(r => r.json());
            if (res.status !== 'success') return AppSwal.error(res.message);
            this.mapels = this.mapels.filter(x => x.id !== m.id);
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus mata pelajaran.');
        } finally {
            this.isLoading = false;
        }
    },

    resetGuruForm() {
        this.guruForm = { id: null, name: '' };
    },
    editGuru(g) {
        this.guruForm = { id: g.id, name: g.name };
    },
    async simpanGuru() {
        this.isLoading = true;
        try {
            const isEdit = !!this.guruForm.id;
            const url = isEdit ? `${this.routes.guruBase}/${this.guruForm.id}` : this.routes.guruStore;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', this.guruForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (isEdit) {
                const idx = this.gurus.findIndex(g => g.id === res.data.id);
                if (idx !== -1) this.gurus[idx] = { ...this.gurus[idx], name: res.data.name };
            } else {
                this.gurus.push({ id: res.data.id, name: res.data.name, jumlah_baris_jadwal: 0, bisa_dihapus: true });
            }
            AppSwal.toast(res.message);
            this.resetGuruForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan guru.');
        } finally {
            this.isLoading = false;
        }
    },
    async hapusGuru(g) {
        const confirmation = await AppSwal.confirm('Hapus guru?', `"${g.name}" akan dihapus permanen.`, 'Ya, hapus');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const res = await fetch(`${this.routes.guruBase}/${g.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            }).then(r => r.json());
            if (res.status !== 'success') return AppSwal.error(res.message);
            this.gurus = this.gurus.filter(x => x.id !== g.id);
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus guru.');
        } finally {
            this.isLoading = false;
        }
    },

    resetRuangForm() {
        this.ruangForm = { id: null, name: '' };
    },
    editRuang(r) {
        this.ruangForm = { id: r.id, name: r.name };
    },
    async simpanRuang() {
        this.isLoading = true;
        try {
            const isEdit = !!this.ruangForm.id;
            const url = isEdit ? `${this.routes.ruangBase}/${this.ruangForm.id}` : this.routes.ruangStore;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', this.ruangForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (isEdit) {
                const idx = this.ruangs.findIndex(r => r.id === res.data.id);
                if (idx !== -1) this.ruangs[idx] = { ...this.ruangs[idx], name: res.data.name };
            } else {
                this.ruangs.push({ id: res.data.id, name: res.data.name, jumlah_baris_jadwal: 0, bisa_dihapus: true });
            }
            AppSwal.toast(res.message);
            this.resetRuangForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan ruang.');
        } finally {
            this.isLoading = false;
        }
    },
    async hapusRuang(r) {
        const confirmation = await AppSwal.confirm('Hapus ruang?', `"${r.name}" akan dihapus permanen.`, 'Ya, hapus');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const res = await fetch(`${this.routes.ruangBase}/${r.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            }).then(r => r.json());
            if (res.status !== 'success') return AppSwal.error(res.message);
            this.ruangs = this.ruangs.filter(x => x.id !== r.id);
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus ruang.');
        } finally {
            this.isLoading = false;
        }
    },

    resetSesiForm() {
        this.sesiForm = { id: null, name: '', start_time: '', end_time: '' };
    },
    editSesi(s) {
        this.sesiForm = {
            id: s.id,
            name: s.name,
            start_time: String(s.start_time || '').substring(0, 5),
            end_time: String(s.end_time || '').substring(0, 5),
        };
    },
    async simpanSesi() {
        this.isLoading = true;
        try {
            const isEdit = !!this.sesiForm.id;
            const url = isEdit ? `${this.routes.sesiBase}/${this.sesiForm.id}` : this.routes.sesiStore;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', this.sesiForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (isEdit) {
                const idx = this.sesis.findIndex(s => s.id === res.data.id);
                if (idx !== -1) this.sesis[idx] = { ...this.sesis[idx], ...res.data };
            } else {
                this.sesis.push({ ...res.data, jumlah_baris_jadwal: 0, bisa_dihapus: true });
            }
            AppSwal.toast(res.message);
            this.resetSesiForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan sesi.');
        } finally {
            this.isLoading = false;
        }
    },
    async hapusSesi(s) {
        const confirmation = await AppSwal.confirm('Hapus sesi?', `"${s.name}" akan dihapus permanen.`, 'Ya, hapus');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const res = await fetch(`${this.routes.sesiBase}/${s.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            }).then(r => r.json());
            if (res.status !== 'success') return AppSwal.error(res.message);
            this.sesis = this.sesis.filter(x => x.id !== s.id);
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus sesi.');
        } finally {
            this.isLoading = false;
        }
    },

    resetKemampuanForm() {
        this.kemampuanForm = { id: null, keterangan: '' };
    },
    editKemampuan(k) {
        this.kemampuanForm = { id: k.id, keterangan: k.keterangan };
    },
    bisaHapusKemampuan(k) {
        const levelTertinggi = Math.max(...this.kemampuans.map(x => x.level));
        return k.jumlah_siswa === 0 && k.level === levelTertinggi;
    },
    async simpanKemampuan() {
        this.isLoading = true;
        try {
            const isEdit = !!this.kemampuanForm.id;
            const url = isEdit ? `${this.routes.kemampuanBase}/${this.kemampuanForm.id}` : this.routes.kemampuanStore;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', { keterangan: this.kemampuanForm.keterangan });
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (isEdit) {
                const idx = this.kemampuans.findIndex(k => k.id === res.data.id);
                if (idx !== -1) this.kemampuans[idx] = { ...this.kemampuans[idx], keterangan: res.data.keterangan };
            } else {
                this.kemampuans.push({ id: res.data.id, level: res.data.level, keterangan: res.data.keterangan, jumlah_siswa: 0 });
            }
            AppSwal.toast(res.message);
            this.resetKemampuanForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan tingkat kemampuan.');
        } finally {
            this.isLoading = false;
        }
    },
    async hapusKemampuan(k) {
        const confirmation = await AppSwal.confirm('Hapus tingkat kemampuan?', `Level ${k.level} — "${k.keterangan}" akan dihapus permanen.`, 'Ya, hapus');
        if (!confirmation.isConfirmed) return;
        this.isLoading = true;
        try {
            const res = await fetch(`${this.routes.kemampuanBase}/${k.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            }).then(r => r.json());
            if (res.status !== 'success') return AppSwal.error(res.message);
            this.kemampuans = this.kemampuans.filter(x => x.id !== k.id);
            AppSwal.toast(res.message);
        } catch (e) {
            AppSwal.error('Gagal menghapus tingkat kemampuan.');
        } finally {
            this.isLoading = false;
        }
    },

    resetSiswaForm() {
        this.siswaForm = { id: null, name: '', panggilan: '', kelas: '', no_hp: '', paket_pembayaran: '', tingkat_kemampuan_id: '' };
    },
    editSiswa(s) {
        this.siswaForm = {
            id: s.id,
            name: s.name || '',
            panggilan: s.panggilan || '',
            kelas: s.kelas || '',
            no_hp: s.no_hp || '',
            paket_pembayaran: s.paket_pembayaran == null ? '' : String(s.paket_pembayaran),
            tingkat_kemampuan_id: s.tingkat_kemampuan_id == null ? '' : String(s.tingkat_kemampuan_id),
        };
    },
    async simpanSiswa() {
        this.isLoading = true;
        try {
            const isEdit = !!this.siswaForm.id;
            const url = isEdit ? `${this.routes.siswaBase}/${this.siswaForm.id}` : this.routes.siswaStore;
            const res = await kirim(url, isEdit ? 'PUT' : 'POST', this.siswaForm);
            if (res.status !== 'success') return AppSwal.error(res.message);

            if (isEdit) {
                const idx = this.siswas.findIndex(s => s.id === res.data.id);
                if (idx !== -1) this.siswas[idx] = { ...this.siswas[idx], ...res.data };
            } else {
                this.siswas.push({ ...res.data, tandas: [] });
            }
            AppSwal.toast(res.message);
            this.resetSiswaForm();
        } catch (e) {
            AppSwal.error('Gagal menyimpan siswa.');
        } finally {
            this.isLoading = false;
        }
    },

    async importSiswaMassal(event) {
        const file = event.target.files[0];
        if (!file) return;

        const confirmation = await AppSwal.confirm(
            'Import data siswa?',
            'Siswa dengan nama yang sudah ada akan diperbarui, yang belum ada akan ditambahkan baru.',
            'Ya, import'
        );
        if (!confirmation.isConfirmed) {
            event.target.value = '';
            return;
        }

        this.isLoading = true;
        try {
            const formData = new FormData();
            formData.append('file', file);

            const res = await fetch(this.routes.siswaImport, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                body: formData,
            }).then(r => r.json());

            if (res.status !== 'success') return AppSwal.error(res.message);

            await AppSwal.toast(res.message);
            window.location.reload();
        } catch (e) {
            AppSwal.error('Gagal mengimpor data siswa.');
        } finally {
            this.isLoading = false;
            event.target.value = '';
        }
    },
});
