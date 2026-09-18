<?php

use App\Http\Controllers\AkunGuruController;
use App\Http\Controllers\ArsipController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiskonController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\GuruPortalController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\ModulAjarController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaporOrangTuaController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RuangController;
use App\Http\Controllers\SesiController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TandaController;
use App\Http\Controllers\TingkatKemampuanController;
use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'guestIndex'])->name('welcome');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('dashboard');

Route::middleware(['auth', 'role:guru'])->group(function () {
    Route::get('/guru', [GuruPortalController::class, 'index'])->name('guru.jadwal');
    Route::get('/guru/gaji', [PayrollController::class, 'milikSaya'])->name('guru.gaji');
});

Route::middleware(['auth', 'role:admin|guru'])
    ->get('/penggajian/{penggajian}/struk-pdf', [PayrollController::class, 'strukPdf'])
    ->name('penggajian.strukPdf');

Route::middleware(['auth', 'role:admin|guru'])->prefix('modul-ajar')->name('modulAjar.')->group(function () {
    Route::get('/', [ModulAjarController::class, 'index'])->name('index');
    Route::post('/header/{kodeKelas}', [ModulAjarController::class, 'simpanHeader'])->name('simpanHeader');
    Route::post('/{modulAjar}/detail', [ModulAjarController::class, 'simpanDetail'])->name('simpanDetail');
    Route::put('/detail/{detail}', [ModulAjarController::class, 'updateDetail'])->name('updateDetail');
    Route::delete('/detail/{detail}', [ModulAjarController::class, 'hapusDetail'])->name('hapusDetail');
    Route::post('/detail/{detail}/persiapan', [ModulAjarController::class, 'mulaiPersiapan'])->name('mulaiPersiapan');
    Route::post('/detail/{detail}/tidak-bisa-hadir', [ModulAjarController::class, 'tandaiTidakBisaHadir'])->name('tandaiTidakBisaHadir');
    Route::post('/detail/{detail}/klaim', [ModulAjarController::class, 'klaimSlotTerbuka'])->name('klaimSlotTerbuka');
    Route::post('/detail/{detail}/nilai', [ModulAjarController::class, 'simpanNilai'])->name('simpanNilai');
});

Route::middleware(['auth', 'role:admin|guru'])->get('/absen', [ModulAjarController::class, 'absen'])->name('absen.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/akun-guru', [AkunGuruController::class, 'index'])->name('admin.akunGuru.index');
    Route::post('/admin/akun-guru/guru/{id}/akun', [AkunGuruController::class, 'buatAkunGuru'])->name('admin.akunGuru.buatAkunGuru');
    Route::put('/admin/akun-guru/guru/{id}/email', [AkunGuruController::class, 'ubahEmailGuru'])->name('admin.akunGuru.ubahEmailGuru');

    Route::get('/admin/workshop', [WorkshopController::class, 'index'])->name('admin.workshop.index');

    Route::post('/admin/jadwal/update-posisi', [JadwalController::class, 'updatePosisi'])->name('admin.jadwal.updatePosisi');
    Route::post('/admin/jadwal/update-kelas', [JadwalController::class, 'updateKelas'])->name('admin.jadwal.updateKelas');
    Route::post('/admin/jadwal/store', [JadwalController::class, 'store'])->name('admin.jadwal.store');
    Route::get('/admin/jadwal/export', [JadwalController::class, 'exportExcel'])->name('admin.jadwal.export');
    Route::get('/admin/jadwal/generate-text', [JadwalController::class, 'generateTextJadwal'])->name('admin.jadwal.generateText');
    Route::get('/admin/jadwal/download-stash', [JadwalController::class, 'downloadStash'])->name('admin.jadwal.downloadStash');
    Route::post('/admin/jadwal/upload-stash', [JadwalController::class, 'uploadStash'])->name('admin.jadwal.uploadStash');
    Route::get('/admin/jadwal/cadangan-stash/{pemulihan}', [JadwalController::class, 'unduhCadanganStash'])->name('admin.jadwal.unduhCadanganStash');

    Route::post('/admin/mapel', [MapelController::class, 'store'])->name('admin.mapel.store');
    Route::put('/admin/mapel/{id}', [MapelController::class, 'update'])->name('admin.mapel.update');
    Route::delete('/admin/mapel/{id}', [MapelController::class, 'destroy'])->name('admin.mapel.destroy');

    Route::post('/admin/guru', [GuruController::class, 'store'])->name('admin.guru.store');
    Route::put('/admin/guru/{id}', [GuruController::class, 'update'])->name('admin.guru.update');
    Route::delete('/admin/guru/{id}', [GuruController::class, 'destroy'])->name('admin.guru.destroy');

    Route::post('/admin/ruang', [RuangController::class, 'store'])->name('admin.ruang.store');
    Route::put('/admin/ruang/{id}', [RuangController::class, 'update'])->name('admin.ruang.update');
    Route::delete('/admin/ruang/{id}', [RuangController::class, 'destroy'])->name('admin.ruang.destroy');

    Route::post('/admin/sesi', [SesiController::class, 'store'])->name('admin.sesi.store');
    Route::put('/admin/sesi/{id}', [SesiController::class, 'update'])->name('admin.sesi.update');
    Route::delete('/admin/sesi/{id}', [SesiController::class, 'destroy'])->name('admin.sesi.destroy');

    Route::get('/admin/siswa/export-excel', [SiswaController::class, 'exportExcel'])->name('admin.siswa.exportExcel');
    Route::get('/admin/siswa/import-template', [SiswaController::class, 'downloadImportTemplate'])->name('admin.siswa.importTemplate');
    Route::post('/admin/siswa/import', [SiswaController::class, 'import'])->name('admin.siswa.import');
    Route::get('/admin/siswa/{siswa}/jadwal', [SiswaController::class, 'jadwal'])->name('admin.siswa.jadwal');
    Route::get('/admin/result', [ResultController::class, 'index'])->name('admin.result.index');
    Route::post('/admin/result/aspek', [ResultController::class, 'simpanAspek'])->name('admin.result.simpanAspek');
    Route::put('/admin/result/aspek/{aspek}', [ResultController::class, 'ubahAspek'])->name('admin.result.ubahAspek');
    Route::delete('/admin/result/aspek/{aspek}', [ResultController::class, 'hapusAspek'])->name('admin.result.hapusAspek');
    Route::put('/admin/result/aspek-urutan', [ResultController::class, 'urutkanAspek'])->name('admin.result.urutkanAspek');
    Route::get('/admin/result/siswa/{siswa}/rapor', [ResultController::class, 'rapor'])->name('admin.result.rapor');
    Route::post('/admin/result/siswa/{siswa}/rapor/cetak', [ResultController::class, 'cetakRapor'])->name('admin.result.cetakRapor');
    Route::post('/admin/result/siswa/{siswa}/sertifikat/cetak', [ResultController::class, 'cetakSertifikat'])->name('admin.result.cetakSertifikat');
    Route::post('/admin/siswa', [SiswaController::class, 'store'])->name('admin.siswa.store');
    Route::put('/admin/siswa/{id}', [SiswaController::class, 'update'])->name('admin.siswa.update');
    Route::delete('/admin/siswa/{id}', [SiswaController::class, 'destroy'])->name('admin.siswa.destroy');

    Route::post('/admin/tanda', [TandaController::class, 'store'])->name('admin.tanda.store');
    Route::put('/admin/tanda/{id}', [TandaController::class, 'update'])->name('admin.tanda.update');
    Route::delete('/admin/tanda/{id}', [TandaController::class, 'destroy'])->name('admin.tanda.destroy');

    Route::post('/admin/pembayaran', [PembayaranController::class, 'store'])->name('admin.pembayaran.store');
    Route::put('/admin/pembayaran/{id}', [PembayaranController::class, 'update'])->name('admin.pembayaran.update');
    Route::delete('/admin/pembayaran/{id}', [PembayaranController::class, 'destroy'])->name('admin.pembayaran.destroy');
    Route::post('/admin/pembayaran/lunas-semua', [PembayaranController::class, 'lunasSemua'])->name('admin.pembayaran.lunasSemua');
    Route::post('/admin/pembayaran/penagihan-massal', [PembayaranController::class, 'penagihanMassal'])->name('admin.pembayaran.penagihanMassal');
    Route::post('/admin/pembayaran/lunas-siswa/{id_siswa}', [PembayaranController::class, 'lunasPerSiswa'])->name('admin.pembayaran.lunasSiswa');
    Route::post('/admin/pembayaran/bayar-siswa/{id_siswa}', [PembayaranController::class, 'bayarPerSiswa'])->name('admin.pembayaran.bayarSiswa');
    Route::post('/admin/pembayaran/ke-lunas-massal/{id_siswa}', [PembayaranController::class, 'keLunasMassal'])->name('admin.pembayaran.keLunasMassal');
    Route::get('/admin/pembayaran/keluarga/{no_hp}/detail', [PembayaranController::class, 'detailKeluarga'])->name('admin.pembayaran.detailKeluarga');
    Route::get('/admin/pembayaran/struk/{no_hp}', [PembayaranController::class, 'printStruk'])->name('admin.pembayaran.struk');
    Route::get('/admin/pembayaran/export', [PembayaranController::class, 'exportExcel'])->name('admin.pembayaran.export');

    Route::post('/admin/paket', [PaketController::class, 'store'])->name('admin.paket.store');
    Route::put('/admin/paket/{id}', [PaketController::class, 'update'])->name('admin.paket.update');
    Route::delete('/admin/paket/{id}', [PaketController::class, 'destroy'])->name('admin.paket.destroy');

    Route::post('/admin/kemampuan', [TingkatKemampuanController::class, 'store'])->name('admin.kemampuan.store');
    Route::put('/admin/kemampuan/{id}', [TingkatKemampuanController::class, 'update'])->name('admin.kemampuan.update');
    Route::delete('/admin/kemampuan/{id}', [TingkatKemampuanController::class, 'destroy'])->name('admin.kemampuan.destroy');

    Route::get('/admin/arsip', [ArsipController::class, 'index'])->name('admin.arsip.index');
    Route::put('/admin/arsip/{id}', [ArsipController::class, 'update'])->name('admin.arsip.restore');
    Route::delete('/admin/arsip/{id}', [ArsipController::class, 'destroy'])->name('admin.arsip.destroy');

    Route::get('/admin/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
    Route::put('/admin/payroll/guru/{guru}/tarif', [PayrollController::class, 'updateTarif'])->name('admin.payroll.updateTarif');
    Route::post('/admin/payroll/guru/{guru}/jalankan', [PayrollController::class, 'jalankan'])->name('admin.payroll.jalankan');
    Route::post('/admin/payroll/jalankan-semua', [PayrollController::class, 'jalankanSemua'])->name('admin.payroll.jalankanSemua');
    Route::get('/admin/payroll/struk/{penggajian}', [PayrollController::class, 'struk'])->name('admin.payroll.struk');
    Route::post('/admin/payroll/struk/{penggajian}/batalkan', [PayrollController::class, 'batalkan'])->name('admin.payroll.batalkan');

    Route::post('/admin/diskon', [DiskonController::class, 'store'])->name('admin.diskon.store');
    Route::put('/admin/diskon/{id}', [DiskonController::class, 'update'])->name('admin.diskon.update');
    Route::delete('/admin/diskon/{id}', [DiskonController::class, 'destroy'])->name('admin.diskon.destroy');
});

Route::get('/rapor-anak', [RaporOrangTuaController::class, 'form'])->name('rapor.publik');
Route::post('/rapor-anak', [RaporOrangTuaController::class, 'cari'])->name('rapor.publik.cari');

Route::get('/jadwal-kalender', [JadwalController::class, 'tampilKalender'])->name('jadwal.kalender');
Route::get('/jadwal-kalender/export', [JadwalController::class, 'exportPdf'])->name('jadwal.kalender.export');

require __DIR__.'/auth.php';
