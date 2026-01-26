<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\KartuSppController;
use App\Http\Controllers\KwitansiPembayaranController;

Route::get('/', function () {
    return redirect('/admin');
});
Route::middleware(['auth'])->group(function () {
    Route::get('admin/kartu-spp/{id}', [KartuSppController::class, 'index'])->name('kartu-spp');
    Route::get('/admin/kartu-alumni/{id}', [KartuSppController::class, 'alumni'])->name('kartu-alumni');

    Route::get('/admin/kwitansi-pembayaran/{nomor_bayar}', [KwitansiPembayaranController::class, 'index'])->name('kwitansi-pembayaran-siswa');
    });
Route::get('/verification/{encrypted_nisn}', [KartuSppController::class, 'verification'])->name('verification');
Route::get('/verification-payment/{encrypted_nomor}', [KwitansiPembayaranController::class, 'verification'])->name('verification-payment');