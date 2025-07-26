<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\KartuSppController;

Route::get('/', function () {
    return redirect('/admin');
});
Route::middleware(['auth'])->group(function () {
    Route::get('admin/kartu-spp/{id}', [KartuSppController::class, 'index'])->name('kartu-spp');
});
Route::get('/kartu-alumni', function() {
    $siswa = \App\Models\Siswa::where('id',2)->with(['tagihans', 'pembayaran'])->first()->toArray();
    //dd($siswa);
     $path = public_path().'/images/logo-sekolah.jpg';
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $image = 'data:image/'.$type.';base64,'.base64_encode($data);
    $pdf = Pdf::loadView('templates.kartu-tagihan-alumni',[
        'siswa' => $siswa,
        'logo' => $image
    ]);
    return $pdf->stream();
});

Route::get('/tes', function() {
    Artisan::call('storage:link');
});