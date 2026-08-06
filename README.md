# Kanban Kalender — E-Ling Course

Sistem operasional bimbel berbasis Laravel untuk mengelola jadwal, data siswa, pembayaran, arsip, catatan siswa, dan tampilan kalender publik dalam satu dashboard admin.

## Highlight

- Dashboard admin multi-tab: Jadwal, Data Siswa, dan Pembayaran
- Kalender publik untuk melihat jadwal aktif
- Penjadwalan kelas per hari, sesi, guru, ruang, dan siswa
- Proteksi bentrok guru, ruang, dan siswa
- Data master siswa + arsip siswa
- Paket pembayaran, diskon keluarga, dan diskon universal
- Tagihan manual dan penagihan massal
- Pembayaran cicilan, pelunasan otomatis sistem, dan cetak bukti
- Export PDF untuk jadwal, siswa, dan pembayaran
- UI admin dark-mode friendly dan responsif

## Modul Utama

### 1. Jadwal

- Kelola mapel, guru, ruang, sesi, dan catatan
- Susun kelas per slot hari dan sesi
- Drag & drop perpindahan kelas
- Copy teks WhatsApp jadwal
- Export PDF jadwal + catatan siswa

### 2. Data Siswa

- Tambah, edit, arsipkan, pulihkan, dan hapus permanen siswa
- Filter berdasarkan kelas, paket, sesi, guru, dan ruang
- Lihat jadwal yang diikuti siswa
- Export PDF daftar siswa sesuai filter aktif

### 3. Pembayaran

- Input tagihan manual
- Penagihan massal berdasarkan paket aktif siswa
- Diskon spesifik keluarga dan diskon universal
- Pembayaran bertahap / cicilan
- Set lunas per keluarga
- Selesaikan seluruh status
- Cetak bukti pelunasan
- Export PDF administrasi pembayaran

## Teknologi

- Laravel 12
- PHP 8.3
- Blade
- Alpine.js
- Tailwind CSS
- SweetAlert2
- DomPDF
- SQLite / MySQL-compatible flow

## Cara Menjalankan

### 1. Install dependency

```bash
composer install
npm install
```

### 2. Siapkan environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Jalankan migrasi

```bash
php artisan migrate --seed
```

### 4. Build frontend

```bash
npm run build
```

### 5. Jalankan server

```bash
php artisan serve
```

## Catatan Setup Lokal Project Ini

Project ini pernah disiapkan agar bisa memakai PHP 8.3 khusus project tanpa mengganggu project lain.

File lokal yang memang tidak untuk dibagikan:

- `php83.ini`
- `project-terminal.cmd`

Keduanya sudah aman untuk di-ignore agar tidak mengubah konfigurasi mesin lain.

## Kondisi Sistem Saat Ini

- Jadwal sudah dilindungi dari bentrok guru, ruang, dan siswa
- Penyimpanan jadwal memakai transaksi
- Nomor WhatsApp siswa memakai format `+62`
- Summary pembayaran dibuat lebih ringan untuk frontend
- Detail keluarga pembayaran di-load saat dibuka
- Export pembayaran, struk, dan tampilan admin sudah diarahkan ke gaya yang lebih formal
- Test fitur penting pembayaran dan jadwal sudah tersedia

## Dokumentasi Lanjutan

Untuk dokumentasi internal sistem yang lebih lengkap, lihat:

- [FEED.md](FEED.md)

## License

Internal project / private workflow.

---

Made by Vayndem with ❤️
