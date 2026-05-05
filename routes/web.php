<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\RuangController;
use App\Http\Controllers\SesiController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TandaController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\ArsipController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // --- Profile ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Jadwal Transaksi / Operasional ---
    Route::post('/admin/jadwal/update-posisi', [JadwalController::class, 'updatePosisi'])->name('admin.jadwal.updatePosisi');
    Route::post('/admin/jadwal/update-kelas', [JadwalController::class, 'updateKelas'])->name('admin.jadwal.updateKelas');
    Route::post('/admin/jadwal/store', [JadwalController::class, 'store'])->name('admin.jadwal.store');
    Route::get('/admin/jadwal/export', [JadwalController::class, 'exportPdf'])->name('admin.jadwal.export');
    Route::get('/admin/jadwal/generate-text', [JadwalController::class, 'generateTextJadwal'])->name('admin.jadwal.generateText');
    Route::get('/admin/jadwal/download-stash', [JadwalController::class, 'downloadStash'])->name('admin.jadwal.downloadStash');
    Route::post('/admin/jadwal/upload-stash', [JadwalController::class, 'uploadStash'])->name('admin.jadwal.uploadStash');

    // --- MANAJEMEN DATA MASTER (CRUD Lengkap) ---

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

    // 8. Paket
    Route::post('/admin/paket', [PaketController::class, 'store'])->name('admin.paket.store');
    Route::put('/admin/paket/{id}', [PaketController::class, 'update'])->name('admin.paket.update');
    Route::delete('/admin/paket/{id}', [PaketController::class, 'destroy'])->name('admin.paket.destroy');

    // 9. Arsip Siswa
    Route::get('/admin/arsip', [ArsipController::class, 'index'])->name('admin.arsip.index');
    Route::put('/admin/arsip/{arsip}', [ArsipController::class, 'update'])->name('admin.arsip.restore');
    Route::delete('/admin/arsip/{arsip}', [ArsipController::class, 'destroy'])->name('admin.arsip.destroy');
});

Route::get('/jadwal-kalender', [JadwalController::class, 'tampilKalender'])->name('jadwal.kalender');

require __DIR__ . '/auth.php';
