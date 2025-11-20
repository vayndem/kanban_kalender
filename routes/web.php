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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // update kanban
    Route::post('/admin/jadwal/update-posisi', [JadwalController::class, 'updatePosisi'])->name('admin.jadwal.updatePosisi');
    Route::post('/admin/jadwal/update-kelas', [JadwalController::class, 'updateKelas'])->name('admin.jadwal.updateKelas');

    Route::post('/admin/jadwal/store', [JadwalController::class, 'store'])->name('admin.jadwal.store');

    Route::post('/admin/mapel', [MapelController::class, 'store'])->name('admin.mapel.store');
    Route::post('/admin/guru', [GuruController::class, 'store'])->name('admin.guru.store');
    Route::post('/admin/ruang', [RuangController::class, 'store'])->name('admin.ruang.store');
    Route::post('/admin/sesi', [SesiController::class, 'store'])->name('admin.sesi.store');
    Route::post('/admin/siswa', [SiswaController::class, 'store'])->name('admin.siswa.store');
});

Route::get('/jadwal-kalender', [JadwalController::class, 'tampilKalender'])->name('jadwal.kalender');

require __DIR__ . '/auth.php';
