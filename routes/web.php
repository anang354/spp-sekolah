<?php

use Illuminate\Support\Facades\Route;
    use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/kartu-alumni', function() {
    $pdf = Pdf::loadView('templates.kartu-tagihan-alumni');
    return $pdf->stream();
});