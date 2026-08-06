# FEED.md

Dokumen ini adalah memori teknis project `kanban_kalender`.

Tujuannya bukan untuk promosi, tapi untuk:

- refresh context dengan cepat saat project lama tidak disentuh
- tahu controller mana mengakses apa
- tahu view mana menerima data dari mana
- tahu test mana memverifikasi flow penting
- tahu area fragile sebelum melakukan perubahan

---

## 1. Identitas Sistem

- Nama: `Kanban Kalender`
- Domain bisnis: operasional bimbel / tutoring admin
- Organisasi: `E-Ling Course`
- Framework: Laravel 12
- Runtime target lokal: PHP 8.3
- Frontend: Blade + Tailwind CSS + Alpine.js
- Interaction layer: SweetAlert2
- Export engine: DomPDF

Fungsi besar sistem:

- kelola jadwal
- kelola siswa aktif dan arsip
- kelola paket dan tagihan
- catat pembayaran
- cetak dan export PDF
- tampilkan kalender publik

---

## 2. Gambaran Arsitektur

Secara kasar sistem dibagi jadi 3 domain admin:

1. Jadwal
2. Data Siswa
3. Pembayaran

Lalu ada 1 area publik:

4. Kalender publik

Pola umum:

- `routes/web.php` mengarah ke controller
- controller mengumpulkan data Eloquent
- Blade admin merender dashboard/tab/modal
- beberapa aksi AJAX/JSON dipakai untuk detail yang lazy-load
- PDF memakai Blade khusus di `resources/views/pdf`

---

## 3. Domain Model yang Paling Sering Terlibat

Entitas utama yang sering muncul:

- `Siswa`
- `Jadwal`
- `Hari`
- `Sesi`
- `Guru`
- `Ruang`
- `MataPelajaran`
- `Paket`
- `Pembayaran`
- `PembayaranDetail`
- `Diskon`
- `Tanda`
- `Arsip`

Catatan relasi penting:

- satu grup kelas secara logika tampil sebagai satu card, tapi di DB bisa tersimpan sebagai banyak row `jadwals` per siswa
- `Pembayaran` adalah header invoice / komponen tagihan per siswa
- `PembayaranDetail` adalah log setoran / cicilan / pelunasan
- diskon keluarga dan diskon universal dihitung di level agregasi pembayaran, bukan dengan mengubah nominal master DB lama

---

## 4. Routing dan Entry Point Penting

File utama routing:

- `routes/web.php`

Yang perlu diingat:

- dashboard admin jangan lagi memuat semua payload sekaligus
- pembayaran detail keluarga sekarang diload melalui endpoint terpisah
- export PDF punya route sendiri dan bergantung pada filter aktif

Endpoint yang wajib diingat:

- dashboard admin
- aksi CRUD jadwal
- aksi CRUD siswa
- aksi pembayaran
- export PDF
- `GET /admin/pembayaran/keluarga/{no_hp}/detail`

Kalau nanti ada refactor route, cek dulu:

- semua tombol Blade
- semua fetch / AJAX
- semua export link
- semua action form

---

## 5. Controller Map

### 5.1 `DashboardController.php`

Peran:

- entry point dashboard admin
- membagi data berdasarkan tab aktif
- mencegah payload terlalu besar

Kenapa penting:

- dulu dashboard pernah memicu `FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE` di Vercel
- sekarang controller ini harus tetap hemat payload

Akses data yang biasanya disentuh:

- jadwal summary
- siswa summary / filter payload
- pembayaran summary

Fragile:

- kalau kembali memuat semua relasi berat sekaligus, deploy serverless bisa jebol lagi
- universal search di dashboard bisa menjadi mahal kalau query relasi ditambah tanpa pembatasan

Harus dicek saat ubah:

- response size
- eager loading yang dipakai
- tab conditional loading

---

### 5.2 `JadwalController.php`

Peran:

- create / update / move / delete jadwal
- validasi bentrok guru, ruang, siswa
- grouping data untuk tampil sebagai card
- export PDF jadwal

Akses DB:

- baca `Hari`, `Sesi`, `Guru`, `Ruang`, `MataPelajaran`, `Siswa`
- tulis ke `Jadwal`
- baca `Tanda` untuk catatan siswa

Flow teknis penting:

- 1 tampilan kelas bisa mewakili banyak row `jadwals`
- proteksi bentrok harus melihat kombinasi:
  - hari
  - sesi
  - guru
  - ruang
  - siswa

Sudah diverifikasi oleh test:

- bentrok guru ditolak
- bentrok ruang ditolak
- bentrok siswa ditolak
- penyimpanan atomik / transactional

Fragile:

- edit partial kelas bisa meninggalkan ketidaksinkronan antar row kalau grouping logic diubah sembarangan
- drag-and-drop antar slot sangat rawan kalau validasi hanya cek sebagian row
- export jadwal rawan mismatch bila query tampilan dan query export berbeda

---

### 5.3 `SiswaController.php`

Peran:

- tambah dan edit siswa
- filter siswa aktif
- bawa relasi jadwal ke modal edit
- export PDF data siswa

Akses DB:

- baca / tulis `Siswa`
- baca relasi `jadwals`, `paket`, `guru`, `ruang`, `sesi`, `hari`

Catatan data:

- nomor HP sekarang diasumsikan sudah benar dalam format `+62...`
- jangan lagi memaksa normalisasi `08...` -> `+62...` di layer yang bisa merusak data existing

Fragile:

- modal edit siswa sangat mudah menampilkan `N/A` kalau eager loading relasi jadwal tidak lengkap
- filter siswa bisa tampak benar di tabel tapi salah di export kalau parameter filter tidak diteruskan penuh
- dropdown guru/ruang/sesi rentan kosong kalau source data filter tidak diload saat render

Sudah pernah jadi masalah:

- jadwal di modal edit muncul `N/A`
- dropdown filter tampil jelek / tidak searchable

---

### 5.4 `ArsipController.php`

Peran:

- mengarsipkan siswa
- memulihkan siswa
- menghapus permanen arsip

Fragile:

- aksi arsip dan restore jangan sampai memutus relasi yang masih dipakai pembayaran lama
- hapus permanen harus dianggap aksi sakral

Catatan UX:

- tombol destructive sebaiknya pakai warna/konfirmasi sakral
- notifikasi sebaiknya lewat SweetAlert, bukan alert browser biasa

---

### 5.5 `PembayaranController.php`

Peran:

- buat tagihan manual
- penagihan massal
- catat pembayaran cicilan
- set lunas
- selesaikan seluruh status
- render detail keluarga
- export PDF pembayaran
- render struk

Ini adalah controller paling fragile saat ini.

#### Akses DB utama

- baca / tulis `Pembayaran`
- baca / tulis `PembayaranDetail`
- baca `Diskon`
- baca `Paket`
- baca `Siswa`

#### Flow penting

1. Tagihan dibuat per siswa
2. Summary dikelompokkan per `no_hp`
3. Detail setoran masuk ke `PembayaranDetail`
4. Status invoice tetap integer lama:
   - `0` = belum bayar
   - `1` = tertagih / proses
   - `2` = lunas
5. UI menampilkan wording yang lebih manusiawi tanpa ubah tipe DB

#### Logika pembayaran cicilan

- nominal masuk tidak boleh melebihi total sisa
- distribusi pembayaran dialokasikan ke tagihan yang relevan
- detail pembayaran harus tercatat
- pembulatan nominal harus aman

#### Logika "set lunas"

- sistem mencari sisa tagihan
- bila ada kekurangan, dibuat `PembayaranDetail`
- keterangan default: `Selesai sistem`
- `total_sudah_dibayar` disamakan dengan kewajiban final

#### Logika "selesaikan seluruh status"

- berlaku ke seluruh komponen tagihan keluarga terkait
- harus mengisi sisa kekurangan, bukan nilai sembarang
- harus tetap meninggalkan jejak detail pembayaran

#### Endpoint lazy detail

- `GET /admin/pembayaran/keluarga/{no_hp}/detail`

Fungsi:

- menghindari frontend mengangkut seluruh detail invoice sekaligus
- modal detail keluarga mengambil data saat dibuka

#### Struk

View:

- `resources/views/pdf/struk.blade.php`

Aturan:

- struk harus mengikuti item invoice terpilih
- struk tidak boleh mengambil invoice di luar konteks filter klik
- jika logo gagal, fallback tanpa logo tetap harus render

#### Export pembayaran

View:

- `resources/views/pdf/pembayaran.blade.php`

Aturan:

- export harus mengikuti filter aktif
- status filter harus terbawa
- bulan filter harus terbawa
- pencarian harus terbawa
- diskon yang relevan harus tampil

Fragile paling tinggi:

- duplikasi tagihan massal
- mismatch antara nominal bersih vs detail pembayaran
- detail pelunasan tidak tercatat saat auto-complete
- export membawa data lebih luas dari filter aktif
- render struk gagal karena path logo / encoding / no_hp URL encoded
- query summary terlalu berat kalau invoice membesar

Sudah diverifikasi oleh test:

- anti-duplicate mass billing
- overpayment rejection
- alokasi pembayaran valid
- `lunasSemua` membuat detail `Selesai sistem`
- struk tetap bisa dirender
- endpoint detail keluarga tersedia

---

## 6. View Map

### Admin

- `resources/views/admin/dashboard.blade.php`
  - shell utama admin
  - routing tab dan universal search

- `resources/views/admin/card.blade.php`
  - area data siswa
  - tabel, filter, aksi, export

- `resources/views/admin/form.blade.php`
  - banyak form/modal untuk entitas operasional

- `resources/views/admin/pembayaran.blade.php`
  - area pembayaran
  - filter bulan/status
  - summary keluarga
  - modal detail
  - tombol cetak struk / export / pelunasan

### PDF

- `resources/views/pdf/jadwal.blade.php`
- `resources/views/pdf/siswa.blade.php`
- `resources/views/pdf/pembayaran.blade.php`
- `resources/views/pdf/struk.blade.php`

Catatan:

- semua PDF sekarang punya header brand seragam tema biru-oranye
- perubahan styling PDF harus dijaga tetap kompatibel dengan DomPDF

Fragile:

- CSS terlalu modern bisa gagal di DomPDF
- gambar/logo pada PDF bisa gagal di environment serverless
- karakter unicode tertentu kadang rusak kalau encoding tidak konsisten

---

## 7. Export Matrix

### Export Jadwal

Source:

- `JadwalController`
- view `resources/views/pdf/jadwal.blade.php`

Harus berisi:

- matriks jadwal
- catatan / tanda siswa
- hasil sesuai search aktif jika ada

Risk:

- tampilan dan hasil export bisa beda jika query divergen

### Export Siswa

Source:

- `SiswaController`
- view `resources/views/pdf/siswa.blade.php`

Harus berisi:

- data siswa sesuai filter aktif
- jadwal yang melekat pada siswa

Risk:

- filter UI belum tentu otomatis terbawa ke export jika parameter tidak sinkron

### Export Pembayaran

Source:

- `PembayaranController`
- view `resources/views/pdf/pembayaran.blade.php`

Harus berisi:

- data sesuai status/bulan/search aktif
- ringkasan administrasi
- kelompok keluarga
- nilai pembayaran yang konsisten

Risk:

- dataset besar
- salah total
- filter tidak sinkron

### Export Struk

Source:

- `PembayaranController`
- view `resources/views/pdf/struk.blade.php`

Harus berisi:

- item lunas yang dipilih
- detail penerimaan pembayaran
- diskon keluarga
- diskon universal

Risk:

- URL encoded `+62...`
- fallback logo
- item yang tercetak tidak sama dengan item yang dipilih user

---

## 8. Frontend / UX Convention

### Sistem warna tombol

File:

- `resources/css/app.css`

Kelas penting:

- `btn-neutral`
- `btn-primary`
- `btn-success`
- `btn-warning`
- `btn-export`
- `btn-accent`
- `btn-sacred`

Makna:

- neutral = batal / kembali
- primary = aksi default kerja
- success = aksi aman / berhasil / restore / copy WA
- warning = proses yang perlu perhatian
- export = generate dokumen
- accent = fitur pendukung
- sacred = aksi sensitif / sakral

### Notifikasi

Target standar:

- gunakan SweetAlert
- hindari alert/confirm browser default

### Dropdown

Ekspektasi UX saat ini:

- dropdown penting harus searchable
- idealnya menampilkan preview item, bukan sekadar value mentah

Fragile:

- ada beberapa dropdown yang historically pernah tampil jelek / tanpa search
- area modal terang vs input gelap pernah bentrok tema

---

## 9. Database Assumption yang Tidak Boleh Dilanggar Sembarangan

1. Status pembayaran tetap integer lama
   - jangan migrasi tipe tanpa keputusan eksplisit

2. Nomor HP keluarga sekarang diperlakukan sudah benar dalam format `+62...`
   - jangan auto-normalize ke format lain

3. Data lama tidak boleh berubah diam-diam hanya karena enhancement UI

4. Diskon dan pelunasan otomatis harus menambah jejak transaksi, bukan overwrite buta

---

## 10. Setup Lokal Khusus Project

Project ini pernah disiapkan agar bisa memakai PHP 8.3 khusus untuk project ini saja.

File lokal:

- `php83.ini`
- `project-terminal.cmd`

Status:

- ini file lokal
- aman untuk di-ignore
- tidak boleh dianggap bagian wajib deploy

Command test yang pernah dipakai:

```powershell
& 'C:\PHP 8.3\php.exe' -c 'D:\Backlash\PRIBADI\kanban_kalender\php83.ini' vendor\bin\phpunit tests\Feature\ScheduleAndPaymentTest.php
```

Build frontend di Windows PowerShell bisa terkendala execution policy.

Fallback yang pernah berhasil:

```powershell
cmd /c npm run build
```

---

## 11. Testing Reference

File test utama:

- `tests/Feature/ScheduleAndPaymentTest.php`

Coverage yang sudah ada:

- pembuatan jadwal atomik
- penolakan bentrok guru
- penolakan bentrok ruang
- penolakan bentrok siswa
- format nomor `+62` tidak dirusak
- alokasi pembayaran tidak salah hitung
- overpayment ditolak
- `lunasSemua` mencatat `Selesai sistem`
- struk bisa dirender
- penagihan massal tidak duplikat
- store tagihan memaksa status awal benar
- dashboard hemat payload per tab
- grouping teks jadwal WhatsApp
- endpoint detail keluarga pembayaran

Kalau menambah fitur di pembayaran, minimal cek lagi:

- total sisa
- total dibayar
- detail pembayaran
- export PDF
- struk
- filter bulan/status

---

## 12. Fragile Area Priority List

Urutan area yang paling rawan kalau diubah:

### Prioritas 1 — Pembayaran

- summary berat
- filter mismatch
- auto-pelunasan salah nominal
- struk salah item
- diskon tidak sinkron

### Prioritas 2 — Jadwal

- bentrok tidak terdeteksi
- drag/drop salah slot
- grouping card dan row DB tidak sinkron

### Prioritas 3 — Data siswa

- relasi jadwal tidak kebawa ke modal edit
- export tidak sesuai filter
- dropdown filter kehilangan source

### Prioritas 4 — PDF / Export

- DomPDF styling pecah
- logo/path asset gagal
- encoding karakter aneh

---

## 13. Checklist Sebelum Menyentuh Fitur Besar

Sebelum ubah controller penting:

- cek route yang memanggilnya
- cek Blade yang tergantung padanya
- cek export PDF yang memakai dataset serupa
- cek test feature yang relevan

Sebelum ubah pembayaran:

- hitung ulang skenario cicilan
- hitung diskon
- cek `set lunas`
- cek `selesaikan seluruh status`
- cek struk

Sebelum ubah jadwal:

- cek validasi bentrok
- cek transactional store
- cek grouping card
- cek export jadwal

---

## 14. Jika Nanti Project Ini Dibuka Lagi

Urutan baca paling cepat untuk refresh:

1. `README.md`
2. `FEED.md`
3. `routes/web.php`
4. `DashboardController.php`
5. `PembayaranController.php`
6. `JadwalController.php`
7. `tests/Feature/ScheduleAndPaymentTest.php`

Kalau bug ada di pembayaran, langsung audit:

- filter aktif
- summary grouping by `no_hp`
- detail endpoint keluarga
- detail `PembayaranDetail`
- export PDF / struk path
