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
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\ProfileController;
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
});

Route::middleware(['auth', 'role:admin|guru'])->prefix('modul-ajar')->name('modulAjar.')->group(function () {
    Route::get('/', [ModulAjarController::class, 'index'])->name('index');
    Route::post('/header/{kodeKelas}', [ModulAjarController::class, 'simpanHeader'])->name('simpanHeader');
    Route::post('/{modulAjar}/detail', [ModulAjarController::class, 'simpanDetail'])->name('simpanDetail');
    Route::put('/detail/{detail}', [ModulAjarController::class, 'updateDetail'])->name('updateDetail');
    Route::delete('/detail/{detail}', [ModulAjarController::class, 'hapusDetail'])->name('hapusDetail');
    Route::post('/detail/{detail}/persiapan', [ModulAjarController::class, 'mulaiPersiapan'])->name('mulaiPersiapan');
    Route::post('/detail/{detail}/nilai', [ModulAjarController::class, 'simpanNilai'])->name('simpanNilai');
});

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

    // --- Jadwal Transaksi / Operasional ---
    Route::post('/admin/jadwal/update-posisi', [JadwalController::class, 'updatePosisi'])->name('admin.jadwal.updatePosisi');
    Route::post('/admin/jadwal/update-kelas', [JadwalController::class, 'updateKelas'])->name('admin.jadwal.updateKelas');
    Route::post('/admin/jadwal/store', [JadwalController::class, 'store'])->name('admin.jadwal.store');
    Route::get('/admin/jadwal/export', [JadwalController::class, 'exportExcel'])->name('admin.jadwal.export');
    Route::get('/admin/jadwal/generate-text', [JadwalController::class, 'generateTextJadwal'])->name('admin.jadwal.generateText');
    Route::get('/admin/jadwal/download-stash', [JadwalController::class, 'downloadStash'])->name('admin.jadwal.downloadStash');
    Route::post('/admin/jadwal/upload-stash', [JadwalController::class, 'uploadStash'])->name('admin.jadwal.uploadStash');

    // 1. Mata Pelajaran
    Route::post('/admin/mapel', [MapelController::class, 'store'])->name('admin.mapel.store');
    Route::put('/admin/mapel/{id}', [MapelController::class, 'update'])->name('admin.mapel.update');
    Route::delete('/admin/mapel/{id}', [MapelController::class, 'destroy'])->name('admin.mapel.destroy');

    // 2. Guru
    Route::post('/admin/guru', [GuruController::class, 'store'])->name('admin.guru.store');
    Route::put('/admin/guru/{id}', [GuruController::class, 'update'])->name('admin.guru.update');
    Route::delete('/admin/guru/{id}', [GuruController::class, 'destroy'])->name('admin.guru.destroy');

    // 3. Ruang
    Route::post('/admin/ruang', [RuangController::class, 'store'])->name('admin.ruang.store');
    Route::put('/admin/ruang/{id}', [RuangController::class, 'update'])->name('admin.ruang.update');
    Route::delete('/admin/ruang/{id}', [RuangController::class, 'destroy'])->name('admin.ruang.destroy');

    // 4. Sesi
    Route::post('/admin/sesi', [SesiController::class, 'store'])->name('admin.sesi.store');
    Route::put('/admin/sesi/{id}', [SesiController::class, 'update'])->name('admin.sesi.update');
    Route::delete('/admin/sesi/{id}', [SesiController::class, 'destroy'])->name('admin.sesi.destroy');

    // 5. Siswa
    Route::get('/admin/siswa/export-excel', [SiswaController::class, 'exportExcel'])->name('admin.siswa.exportExcel');
    Route::get('/admin/siswa/import-template', [SiswaController::class, 'downloadImportTemplate'])->name('admin.siswa.importTemplate');
    Route::post('/admin/siswa/import', [SiswaController::class, 'import'])->name('admin.siswa.import');
    Route::get('/admin/siswa/{siswa}/jadwal', [SiswaController::class, 'jadwal'])->name('admin.siswa.jadwal');
    Route::post('/admin/siswa', [SiswaController::class, 'store'])->name('admin.siswa.store');
    Route::put('/admin/siswa/{id}', [SiswaController::class, 'update'])->name('admin.siswa.update');
    Route::delete('/admin/siswa/{id}', [SiswaController::class, 'destroy'])->name('admin.siswa.destroy');

    // 6. Tanda / Catatan
    Route::post('/admin/tanda', [TandaController::class, 'store'])->name('admin.tanda.store');
    Route::put('/admin/tanda/{id}', [TandaController::class, 'update'])->name('admin.tanda.update');
    Route::delete('/admin/tanda/{id}', [TandaController::class, 'destroy'])->name('admin.tanda.destroy');

    // 7. Pembayaran
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

    // 8. Paket
    Route::post('/admin/paket', [PaketController::class, 'store'])->name('admin.paket.store');
    Route::put('/admin/paket/{id}', [PaketController::class, 'update'])->name('admin.paket.update');
    Route::delete('/admin/paket/{id}', [PaketController::class, 'destroy'])->name('admin.paket.destroy');

    // 8b. Tingkat Kemampuan
    Route::post('/admin/kemampuan', [TingkatKemampuanController::class, 'store'])->name('admin.kemampuan.store');
    Route::put('/admin/kemampuan/{id}', [TingkatKemampuanController::class, 'update'])->name('admin.kemampuan.update');
    Route::delete('/admin/kemampuan/{id}', [TingkatKemampuanController::class, 'destroy'])->name('admin.kemampuan.destroy');

    // 9. Arsip Siswa
    Route::get('/admin/arsip', [ArsipController::class, 'index'])->name('admin.arsip.index');
    Route::put('/admin/arsip/{id}', [ArsipController::class, 'update'])->name('admin.arsip.restore');
    Route::delete('/admin/arsip/{id}', [ArsipController::class, 'destroy'])->name('admin.arsip.destroy');

    // 10. Diskon
    Route::post('/admin/diskon', [DiskonController::class, 'store'])->name('admin.diskon.store');
    Route::put('/admin/diskon/{id}', [DiskonController::class, 'update'])->name('admin.diskon.update');
    Route::delete('/admin/diskon/{id}', [DiskonController::class, 'destroy'])->name('admin.diskon.destroy');
});

Route::get('/jadwal-kalender', [JadwalController::class, 'tampilKalender'])->name('jadwal.kalender');
Route::get('/jadwal-kalender/export', [JadwalController::class, 'exportPdf'])->name('jadwal.kalender.export');

require __DIR__.'/auth.php';
