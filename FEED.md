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
- Frontend: Blade + Tailwind CSS v4 + daisyUI v5 + Alpine.js
- Interaction layer: SweetAlert2
- Export engine: DomPDF (PDF) + maatwebsite/excel (XLSX)
- Otorisasi: spatie/laravel-permission (peran `admin` dan `guru`)

Fungsi besar sistem:

- kelola jadwal
- kelola siswa aktif dan arsip
- kelola paket, tagihan, dan tingkat kemampuan siswa
- catat pembayaran
- isi modul ajar per kelas, catat mengajar dan nilai siswa
- hitung kehadiran guru dan terbitkan struk penggajian
- cetak dan export PDF/Excel
- tampilkan kalender publik
- portal guru read-only (jadwal, modul ajar, absen, gaji sendiri)

---

## 2. Gambaran Arsitektur

Domain admin (semua di balik `role:admin`):

1. Ringkasan — dashboard operasional, pengingat finansial, kebersihan data
2. Jadwal
3. Data Siswa
4. Pembayaran
5. Workshop — satu-satunya tempat CRUD data pokok (siswa, guru, ruang, sesi, mapel, paket, kemampuan)
6. Payroll — tarif per guru, struk penggajian, tutup periode
7. Akun Guru — khusus akun login guru

Dipakai bersama admin dan guru (`role:admin|guru`):

8. Modul Ajar — kurikulum per kelas
9. Absen — papan mengajar hari x sesi, penilaian siswa

Khusus guru (`role:guru`):

10. Portal Guru — jadwal sendiri, dan `/guru/gaji` untuk struk gajinya sendiri

Area publik tanpa login:

11. Kalender publik — sengaja tidak membocorkan data internal (paket, kemampuan, tagihan)

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

Total saat ini: **208 test PHPUnit** (SQLite in-memory, tidak pernah menyentuh MySQL lokal maupun produksi) + 3 test JS (`npm run test:js`).

File test utama:

- `tests/Feature/ScheduleAndPaymentTest.php`

Suite pendukung yang perlu diketahui:

- `PayrollTest` — aritmetika struk, hitungan kehadiran kembali nol tanpa menghapus riwayat, tarif dibekukan, guru tanpa kehadiran tetap dapat gaji bawaan, penjaga klik ganda, pembatalan melepas kehadiran, dan guru hanya bisa melihat/mengunduh struknya sendiri
- `SesiWaktuTest` — jam sesi terbaca `HH:MM` (bukan ISO), urut menurut waktu, durasi tidak pernah negatif
- `RaporSiswaTest` — agregasi kehadiran/nilai, filter tanggal, ambang 4 nilai sebelum tren muncul, unduh PDF, guru ditolak
- `ModulAjarTest` — scoping admin vs guru, split izin create/update, `kode_kelas` bertahan melewati drag/edit/stash, alur mengajar-pengganti-nilai
- `PeranDanPortalGuruTest`, `AkunGuruTest`, `WorkshopTest`, `SiswaImportTest`, `ExportPhoneNumberFormatTest`, `PerlindunganHapusDanUbahTest`, `AntiDoubleClickTest`, `RapikanDuplikatPembayaranTest`, `NormalisasiNomorHpTest`, `ProduksiTerlindungiTest`, `DemoSeederTest`, `RingkasanDashboardTest`

Di luar PHPUnit, tampilan diverifikasi dengan menjalankan Chrome sungguhan lewat Playwright: kontras diukur pada setiap simpul teks di 10 layar untuk kedua tema, dan tata letak dicek di 375px/768px untuk overflow horizontal serta ukuran area sentuh.

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

## 11b. Modul Yang Ditambahkan Setelah Dokumen Ini Pertama Ditulis

Bagian ini merangkum fitur yang lahir setelah versi awal FEED.md, supaya tidak ada lagi
celah antara dokumen dan kode. Rincian invarian dan alasannya ada di `CLAUDE.md`.

### Peran dan portal guru

Dua peran lewat spatie/laravel-permission: `admin` dan `guru`. Dijaga di lapisan rute,
bukan dengan menyembunyikan tombol. `users.guru_id` menautkan akun login ke entitas `Guru`;
arahnya penting — `Guru` yang dirujuk `jadwals`, `User` hanya pintu masuk. Menghapus user
tidak pernah menyentuh jadwal.

### Tingkat Kemampuan

Level 1..N dengan keterangan bebas, satu siswa satu kemampuan (`siswas.tingkat_kemampuan_id`).
Penomorannya wajib berurutan: menghapus hanya boleh dari level tertinggi ke bawah. Data ini
internal — tidak boleh bocor ke kalender publik.

### Modul Ajar dan `kode_kelas`

`jadwals.kode_kelas` (UUID) adalah identitas kelas yang bertahan walau kelas digeser hari/sesi
atau diedit lewat modal. Kombinasi hari+sesi+mapel+guru+ruang **tidak** aman dipakai sebagai
identitas jangka panjang. Satu `modul_ajars` per `kode_kelas`, punya banyak `modul_ajar_details`.
Izinnya dipisah per kata kerja: admin dan guru pengampu boleh membuat, hanya admin boleh
mengubah/menghapus.

### Absen, penilaian, dan guru pengganti

Tiap baris `modul_ajar_details` sekaligus menjadi peristiwa mengajar satu kali. Guru menandai
dirinya tidak bisa hadir, slotnya langsung terbuka untuk semua guru (rebutan, siapa cepat).
Kredit kehadiran mengikuti siapa yang benar-benar mengajar dan menilai, bukan pemilik jadwal.
Satu baris `absensi_gurus` per detail yang dinilai, dihitung **per sesi**, bukan per hari —
guru yang menyelesaikan 3 kelas dalam sehari mendapat 3.

### Rapor Perkembangan Siswa

`RaporService` mengagregasi `modul_ajar_absensis` per siswa: kehadiran, rata-rata/terendah/tertinggi,
dan tren naik/turun/stabil. Tren baru muncul setelah minimal 4 nilai. Tersedia sebagai JSON di
panel detail siswa dan sebagai PDF (`pdf/rapor.blade.php`).

### Payroll

Tarif ada di `gurus` (`gaji_bawaan`, `gaji_per_kehadiran`), struk di `penggajians`.

- Penggajian dijalankan **ad-hoc**, bukan per bulan. Gaji bawaan dibayar **penuh setiap run**.
  Guru tanpa kehadiran tetap menerima struk berisi gaji bawaan saja — itu memang disengaja.
- Tombol "Siap Lakukan" **menutup periode, bukan menghapus**: semua `absensi_gurus` milik guru itu
  yang `penggajian_id IS NULL` distempel dengan id struk. Penghitung berjalan membaca
  `WHERE penggajian_id IS NULL`, jadi jatuh ke nol dengan sendirinya sementara riwayat utuh.
  **Jangan pernah mengubah reset ini menjadi DELETE.**
- Tarif dibekukan ke struk, sehingga kenaikan gaji di kemudian hari tidak mengubah struk lama.
- Koreksi struk = batalkan lalu terbitkan baru, bukan edit. Pembatalan melepas kehadirannya kembali.
- Penjaga klik ganda berbasis waktu (`PayrollService::JEDA_ANTI_GANDA`), bukan jumlah baris,
  karena run tanpa kehadiran itu sah.
- Guru bisa melihat gajinya sendiri di `/guru/gaji` (read-only) dan mengunduh struknya sendiri;
  admin bisa mengunduh milik siapa pun. Penjagaan kepemilikan ada di `PayrollController`.

### Design system

Tailwind v4 + daisyUI v5, dikonfigurasi **CSS-first**. `tailwind.config.js` dan `postcss.config.js`
sudah **tidak ada** — seluruh tema hidup di `resources/css/app.css`. Warna memakai token semantik
(`base-100`, `base-content`, `primary`, dst), dark mode ikut preferensi sistem tanpa varian `dark:`.
Tiap peran tombol punya warna sendiri; jangan menyeragamkannya jadi satu keluarga warna.

---

## 11c. Aturan Waktu Sesi (wajib dibaca sebelum menyentuh jadwal)

Sesi **bukan** slot yang saling eksklusif. Jam mulainya berjarak 30 menit sementara
durasinya 60 menit, jadi jalur "jam" dan jalur "setengah jam" saling menyisip.
Di produksi: 17 sesi, **11 pasangan beririsan**.

Akibatnya ruang, guru, dan siswa yang terpakai di satu sesi ikut terkunci di sesi
lain yang jamnya bertindih. `App\Services\IrisanSesiService` adalah satu-satunya
definisi hal ini; **jangan pernah memfilter dengan `sesi_id = ?`** di:

- `JadwalController::ensureNoConflicts()` (simpan, geser, edit kelas)
- `WorkshopController::petaKetersediaan()` (Slot Kosong)
- `RingkasanService::bentrokTersembunyi()` dan `okupansiRuang()`
- `DemoSeeder` (penjaganya sendiri, dan dijaga `DemoSeederTest`)

Bersentuhan tepat di ujung **bukan** bentrok: 13:00-14:00 dan 14:00-15:00 boleh
berbagi ruang. Perbandingannya ketat: `a.start < b.end && b.start < a.end`.

Okupansi ruang memakai `kapasitasSlotPerHari()` sebagai penyebut, bukan jumlah
sesi — memakai satu sesi otomatis mematikan sesi yang bertindih, jadi kapasitas
nyata lebih kecil daripada jumlah sesi terdaftar.

Deteksi "mengajar beruntun" pada `bebanGuru()` diukur dari jam, bukan urutan sesi
dalam daftar: sesi yang benar-benar menyambung berjarak dua posisi, sedangkan
posisi bersebelahan justru biasanya berarti bertindih.

## 11d. `hari_id` Bukan Nomor Hari ISO

Beberapa tempat dulu menyaring "hari ini" dengan `where('hari_id', isoFormat('E'))`.
Itu hanya benar selama baris `haris` kebetulan ber-id 1..7 berurutan. Hapus satu
hari lalu buat ulang -- atau restore ke database yang memberi id berbeda -- dan
Ringkasan diam-diam menampilkan hari yang salah atau kosong. Pakai
`Hari::idHariIni()`, yang mencocokkan **nama** hari dan hanya jatuh ke nomor ISO
sebagai cadangan.

---

## 11e. Dua Lubang Keamanan yang Sudah Ditutup

**PDF jadwal publik membocorkan catatan internal siswa.** Rute
`/jadwal-kalender/export` sengaja tanpa login (tombol Export PDF ada di kalender
publik, dan dashboard admin memakai rute yang sama). Ia dulu ikut mengirim
`studentsWithNotes`, sehingga `pdf/jadwal.blade.php` mencetak isi lengkap setiap
`Tanda` -- catatan internal staf tentang siswa -- kepada siapa pun yang tahu URL-nya.
Sekarang catatan hanya dikumpulkan bila `auth()->check() && hasRole('admin')`.
Dijaga `KeamananEksporDanStashTest`, yang benar-benar membaca isi PDF-nya.

**Stash rusak bisa menghapus seluruh jadwal.** `uploadStash()` menghapus semua baris
`jadwals` lalu memasukkan ulang dari berkas. Sekarang ditangani `StashJadwalService`
dengan empat jaminan, berurutan:

1. Struktur berkas divalidasi **sebelum** apa pun dihapus (menyebut baris ke berapa).
2. Id yang dirujuk dipastikan masih ada -- stash yang menyebut guru/ruang/siswa
   terhapus ditolak dengan pesan jelas, bukan error SQL setelah data telanjur hilang.
3. Kondisi sebelum pemulihan diarsipkan ke `stash_pemulihan_logs` (pola sama dengan
   `koreksi_pembayaran_logs`): siapa, kapan, jumlah sebelum/sesudah, dan seluruh
   jadwal lama dalam bentuk `.stash`. Bisa diunduh lewat
   `admin/jadwal/cadangan-stash/{pemulihan}` -- salah pulih kini bisa dibatalkan.
4. Bentrok yang ikut terbawa dihitung dan diberitahukan saat itu juga.

`catch` diperluas ke `\Throwable`; `\Exception` saja tidak menangkap `TypeError`,
yang dulu bisa membuat jadwal terhapus tanpa rollback.

---

## 12. Fragile Area Priority List

Urutan area yang paling rawan kalau diubah:

### Prioritas 1 — Pembayaran

- summary berat
- filter mismatch
- auto-pelunasan salah nominal
- struk salah item
- diskon tidak sinkron

### Prioritas 1b — Payroll

Sama-sama uang, jadi setara rawannya dengan Pembayaran:

- reset kehadiran diubah jadi DELETE (riwayat hilang, struk lama tidak bisa dipertanggungjawabkan)
- tarif tidak dibekukan ke struk (kenaikan gaji diam-diam menulis ulang masa lalu)
- penjaga klik ganda diganti berbasis jumlah baris (run tanpa kehadiran itu sah, jadi selalu lolos)
- penjagaan kepemilikan di `strukPdf` dilonggarkan (guru bisa membaca gaji guru lain)

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
2. `CLAUDE.md` — invarian, konvensi, dan jebakan yang sudah pernah menggigit
3. `FEED.md`
4. `routes/web.php`
5. `DashboardController.php`
6. `PembayaranController.php`
7. `JadwalController.php`
8. `tests/Feature/ScheduleAndPaymentTest.php`

Kalau bug ada di pembayaran, langsung audit:

- filter aktif
- summary grouping by `no_hp`
- detail endpoint keluarga
- detail `PembayaranDetail`
- export PDF / struk path
