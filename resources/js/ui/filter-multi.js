export const filterMulti = () => ({
    buka: false,
    cari: '',

    tutup() {
        this.buka = false;
        this.cari = '';
    },

    alih() {
        this.buka ? this.tutup() : (this.buka = true);
    },

    hasil(opsi) {
        const kunci = this.cari.toLocaleLowerCase('id-ID').trim();
        const daftar = Array.isArray(opsi) ? opsi : [];

        if (kunci === '') return daftar;

        return daftar.filter(item => [item.label, item.sub]
            .filter(teks => teks !== null && teks !== undefined && teks !== '')
            .some(teks => String(teks).toLocaleLowerCase('id-ID').includes(kunci)));
    },

    pilihSemua(terpilih, opsi) {
        const sudah = new Set((terpilih || []).map(String));
        const tambahan = this.hasil(opsi)
            .map(item => String(item.value))
            .filter(nilai => !sudah.has(nilai));

        return [...(terpilih || []), ...tambahan];
    },
});
