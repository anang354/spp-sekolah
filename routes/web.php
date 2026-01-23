<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\KartuSppController;

Route::get('/', function () {
    return redirect('/admin');
});
Route::middleware(['auth'])->group(function () {
    Route::get('admin/kartu-spp/{id}', [KartuSppController::class, 'index'])->name('kartu-spp');
    Route::get('/admin/kartu-alumni/{id}', [KartuSppController::class, 'alumni'])->name('kartu-alumni');
});
Route::get('/verification/{encrypted_nisn}', [KartuSppController::class, 'verification'])->name('verification');