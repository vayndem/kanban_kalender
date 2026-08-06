# FEED.md

Dokumen ini adalah ringkasan memori sistem untuk project `kanban_kalender`. Tujuannya supaya saat project ini dibuka lagi setelah lama, konteks bisa dipulihkan cepat tanpa audit ulang dari nol.

## 1. Identitas Project

- Nama sistem: Kanban Kalender / E-Ling Course Admin
- Framework: Laravel 12
- UI: Blade + Alpine.js + Tailwind CSS + SweetAlert2
- Fokus bisnis:
  - jadwal kelas
  - data siswa
  - pembayaran bimbel
  - arsip siswa
  - kalender publik

## 2. Struktur Fitur Besar

### A. Tab Jadwal

Fungsi:

- membuat kelas per slot hari + sesi
- mengelola mapel, guru, ruang, sesi
- memindahkan kelas antar slot
- edit isi kelas
- export PDF jadwal
- copy teks jadwal WhatsApp
- backup / restore stash jadwal

Entitas yang terlibat:

- `Hari`
- `Sesi`
- `Jadwal`
- `Guru`
- `Ruang`
- `MataPelajaran`
- `Siswa`
- `Tanda`

Catatan penting:

- jadwal dikelompokkan per kombinasi:
  - hari
  - sesi
  - mapel
  - guru
  - ruang
- 1 kelas = beberapa row `jadwals`, masing-masing untuk 1 siswa

Proteksi:

- bentrok guru dicegah
- bentrok ruang dicegah
- bentrok siswa dicegah
- penyimpanan jadwal sudah memakai transaksi

Controller utama:

- `app/Http/Controllers/JadwalController.php`
- `app/Http/Controllers/DashboardController.php`

View utama:

- `resources/views/admin/dashboard.blade.php`
- `resources/views/jadwal_kalender.blade.php`
- `resources/views/pdf/jadwal.blade.php`

### B. Tab Data Siswa

Fungsi:

- tambah siswa
- edit siswa
- arsipkan siswa
- pulihkan dari arsip
- hapus permanen arsip
- filter siswa
- lihat jadwal siswa
- export PDF data siswa

Entitas yang terlibat:

- `Siswa`
- `Arsip`
- `Jadwal`
- `Paket`

Catatan penting:

- siswa aktif dan arsip dipisah di tampilan
- jadwal siswa bisa dilihat saat edit
- nomor HP disimpan dalam format `+62...`

Controller utama:

- `app/Http/Controllers/SiswaController.php`
- `app/Http/Controllers/ArsipController.php`

View utama:

- `resources/views/admin/card.blade.php`
- `resources/views/pdf/siswa.blade.php`

### C. Tab Pembayaran

Fungsi:

- buat tagihan manual
- penagihan massal
- kelola diskon
- kelola paket
- catat pembayaran cicilan
- ubah ke lunas
- selesaikan seluruh status
- cetak bukti pelunasan
- export PDF pembayaran

Entitas yang terlibat:

- `Pembayaran`
- `PembayaranDetail`
- `Diskon`
- `Paket`
- `Siswa`

Controller utama:

- `app/Http/Controllers/PembayaranController.php`
- `app/Http/Controllers/DashboardController.php`

View utama:

- `resources/views/admin/pembayaran.blade.php`
- `resources/views/pdf/pembayaran.blade.php`
- `resources/views/pdf/struk.blade.php`

## 3. Cara Kerja Modul Pembayaran

### Data inti

`Pembayaran` menyimpan:

- siswa target
- nomor HP keluarga
- nominal tagihan
- keterangan tagihan
- status
- total sudah dibayar
- tanggal pembayaran terakhir
- metode pembayaran terakhir

`PembayaranDetail` menyimpan:

- detail setoran / pelunasan
- nominal pembayaran
- keterangan detail
- timestamp

### Status pembayaran saat ini

Masih memakai status integer lama, tanpa ubah DB:

- `0` = Belum Bayar
- `1` = Tertagih / sedang proses
- `2` = Lunas

Di UI terbaru, status ditampilkan dengan wording yang lebih formal.

### Penagihan massal

Logika:

- sistem baca seluruh siswa aktif yang punya paket
- buat tagihan bulanan dari paket tersebut
- jika periode yang sama sudah pernah dibuat untuk siswa + no_hp + keterangan itu, tagihan tidak diduplikasi

### Pembayaran cicilan

Logika:

- input nominal pembayaran
- sistem cari semua tagihan aktif pada nomor HP keluarga
- nominal dialokasikan dari tagihan paling lama ke paling baru
- tidak boleh melebihi total sisa tagihan

### Ubah ke lunas / Selesaikan seluruh status

Logika:

- sistem mencari sisa nominal pada tagihan aktif
- jika masih ada sisa, sistem menambahkan `PembayaranDetail`
- keterangan detail otomatis: `Selesai sistem`
- total dibayar disamakan dengan nominal tagihan
- status jadi `2`

### Diskon

Ada 2 jenis:

- diskon spesifik keluarga (`no_hp` tertentu)
- diskon universal (`no_hp = null`)

Keduanya dijumlahkan pada ringkasan dan struk.

### Struk

Struk:

- hanya untuk tagihan status lunas
- bisa difilter berdasarkan item yang tampil
- menampilkan:
  - identitas keluarga
  - nama siswa
  - rincian komponen tagihan
  - rincian penerimaan pembayaran
  - potongan keluarga
  - potongan universal
  - total kewajiban bersih

Fallback logo:

- kalau logo ada, dipakai
- kalau gagal, render tanpa logo tetap harus jalan

Logo saat ini diharapkan berada di:

- `storage/app/public/Logo.png` atau fallback lain yang tersedia

## 4. Optimasi Penting yang Sudah Pernah Dilakukan

### A. Ringankan dashboard pembayaran

Masalah lama:

- summary pembayaran terlalu berat saat invoice makin banyak
- detail invoice ikut diproses penuh di frontend

Solusi:

- payload summary diperingan
- detail keluarga diambil lewat endpoint terpisah saat modal dibuka

Endpoint detail keluarga:

- `GET /admin/pembayaran/keluarga/{no_hp}/detail`

### B. Vercel payload terlalu besar

Masalah:

- dashboard pernah error `FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE`

Solusi besar:

- `DashboardController` hanya memuat data sesuai tab aktif
- tab `jadwal`, `data_siswa`, dan `pembayaran` tidak lagi me-load seluruh data sekaligus

### C. Konflik jadwal

Masalah:

- guru / ruang / siswa bisa bentrok

Solusi:

- validasi bentrok di controller jadwal
- transaksi pada penyimpanan

### D. Konsistensi WhatsApp

Masalah lama:

- nomor pernah dinormalisasi dengan asumsi `08...`

Kondisi terbaru:

- DB sekarang dianggap sudah benar dalam format `+62...`
- proses tagih WA harus memakai data itu apa adanya

## 5. Dokumentasi Export

### Export Jadwal

Tujuan:

- laporan jadwal + catatan operasional

Output:

- halaman 1: matriks jadwal
- halaman 2: catatan siswa yang punya tanda

Filter:

- pencarian universal bisa ikut memengaruhi hasil export

### Export Siswa

Tujuan:

- laporan data siswa sesuai filter aktif

Filter yang bisa terbawa:

- pencarian
- kelas
- paket
- sesi
- guru
- ruang

### Export Pembayaran

Tujuan:

- laporan administrasi pembayaran sesuai status / filter aktif

Perilaku terbaru:

- mengikuti status filter bila ada
- mengikuti bulan dan pencarian
- menampilkan ringkasan total per kategori status

### Export Struk

Tujuan:

- bukti pelunasan keluarga tertentu

Perilaku:

- mengikuti item invoice yang sedang dipilih di UI
- tidak boleh menarik data di luar konteks filter klik

## 6. Sistem Warna Tombol

Sudah mulai dibuat konsisten lewat kelas reusable di:

- `resources/css/app.css`

Makna warna:

- `btn-neutral`
  - batal / kembali / aksi normal netral
- `btn-primary`
  - simpan / tambah / catat / aksi kerja standar
- `btn-success`
  - aksi berhasil / restore / WhatsApp / backup aman
- `btn-warning`
  - aksi proses / penagihan / butuh perhatian
- `btn-export`
  - export / generate dokumen
- `btn-accent`
  - fitur pendukung seperti paket / diskon / cetak bukti
- `btn-sacred`
  - aksi sakral / berdampak besar / harus ekstra hati-hati
  - contoh:
    - set lunas
    - selesaikan seluruh status
    - nanti cocok juga untuk replace stash / hapus permanen

## 7. File Penting

### Controller

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/JadwalController.php`
- `app/Http/Controllers/SiswaController.php`
- `app/Http/Controllers/PembayaranController.php`

### View admin

- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/card.blade.php`
- `resources/views/admin/form.blade.php`
- `resources/views/admin/pembayaran.blade.php`

### View PDF

- `resources/views/pdf/jadwal.blade.php`
- `resources/views/pdf/siswa.blade.php`
- `resources/views/pdf/pembayaran.blade.php`
- `resources/views/pdf/struk.blade.php`

### Layout / bantuan

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin-help.blade.php`

### Styling

- `resources/css/app.css`

### Routing

- `routes/web.php`

## 8. Setup Lokal Khusus Project

Pernah ada kebutuhan memakai PHP 8.3 khusus project ini tanpa mengganggu project lain.

File lokal terkait:

- `php83.ini`
- `project-terminal.cmd`

Status:

- file ini untuk lokal
- sudah disiapkan agar aman di-ignore

Kalau test di Windows bermasalah, jalankan langsung dengan PHP 8.3 + config project, contoh:

```powershell
& 'C:\PHP 8.3\php.exe' -c 'D:\Backlash\PRIBADI\kanban_kalender\php83.ini' vendor\bin\phpunit tests\Feature\ScheduleAndPaymentTest.php
```

## 9. Testing yang Sudah Ada

File test utama:

- `tests/Feature/ScheduleAndPaymentTest.php`

Sudah mencakup:

- pembuatan jadwal atomik
- penolakan bentrok guru/ruang/siswa
- nomor `+62` tetap dipertahankan
- alokasi pembayaran tanpa pembulatan salah
- overpayment ditolak
- `lunasSemua` membuat detail `Selesai sistem`
- struk tetap bisa dirender
- penagihan massal tidak duplikat
- store tagihan paksa status awal belum dibayar
- dashboard payload per tab
- grouping teks jadwal WhatsApp
- endpoint detail keluarga pembayaran

## 10. Prinsip Aman Saat Lanjut Develop

Kalau mau lanjut kerja di project ini, pegang prinsip ini:

- jangan ubah struktur DB kalau belum benar-benar perlu
- jangan ubah histori pembayaran lama sembarangan
- prioritaskan perubahan di:
  - view
  - validasi
  - wording
  - report/export
  - safety guard
- kalau ada koreksi transaksi, lebih aman tambah jejak baru daripada overwrite histori

## 11. TODO / Arah Lanjutan yang Masih Bagus

Kalau mau lanjut lagi, ini kandidat berikutnya:

- rapikan seluruh tombol di semua halaman admin ke sistem warna baru
- bikin export pembayaran landscape bila data sangat lebar
- tambah nomor dokumen / nomor kuitansi tanpa merusak histori lama
- tambah audit log tanpa mengubah transaksi lama
- tambah closing bulanan versi ringan
- tambah FEED section changelog per revisi

## 12. Ringkasan Super Singkat

Kalau nanti aku dibuka lagi dan cuma baca 1 menit, inti project ini:

- ini sistem admin bimbel E-Ling
- inti besar ada 3: jadwal, siswa, pembayaran
- pembayaran sudah dioptimasi supaya summary ringan
- detail pembayaran sekarang lazy-load
- jadwal sudah anti bentrok
- no HP pakai `+62`
- status pembayaran masih integer lama, tapi tampilan sudah dibuat lebih formal
- export sedang diarahkan jadi sesuai fungsi masing-masing

