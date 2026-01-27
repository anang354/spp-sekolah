<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    //
    public function alamatSambung()
    {
        // 1. Ambil Data Siswa beserta Relasinya (Eager Loading)
        // Pastikan relasi 'alamat' (alamatSambung) dan 'tagihan' sudah ada di Model Siswa
        $siswas = \App\Models\Siswa::with(['alamatSambung', 'tagihans.pembayaran'])
            ->where('is_active', true) // Filter hanya siswa aktif
            ->get();

        // 2. Lakukan Grouping Berdasarkan Nama Desa
        // Asumsi: di tabel 'alamats' ada kolom 'nama_desa'
        $groupedData = $siswas->groupBy(function ($siswa) {
            return $siswa->alamatSambung->desa ?? 'Tanpa Desa'; 
        })->sortKeys();

        // 3. Hitung Total Tunggakan Per Desa (Pre-calculation)
        // Kita siapkan array ringkasan agar di Blade tinggal tampil saja
        $groupedData = $groupedData->map(function ($listSiswa) {
        
        // A. Hitung dulu total hutang setiap siswa dalam grup ini
        $listSiswa->each(function ($siswa) {
            $siswa->sisa_tagihan_total = $siswa->tagihans->sum(function ($tagihan) {
                return $tagihan->jumlah_netto - $tagihan->pembayaran->sum('jumlah_dibayar');
            });
        });

        // B. Urutkan siswa berdasarkan 'sisa_tagihan_total' dari Besar ke Kecil
        return $listSiswa->sortByDesc('sisa_tagihan_total'); 
    });

    // 4. Hitung Total Summary Per Desa (Untuk Footer Tabel)
    $summary = $groupedData->map(function ($group) {
        return $group->sum('sisa_tagihan_total');
    });
    //dd($groupedData);
         $path = public_path().'/images/logo-sma.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $image = 'data:image/'.$type.';base64,'.base64_encode($data);
        // 4. Generate PDF
        $pdf = Pdf::loadView('templates.laporan-perdesa', [
            'groupedData' => $groupedData,
            'summary' => $summary,
            'logo' => $image,
            'tanggalCetak' => now()->translatedFormat('d F Y')
        ]);

        // Set ukuran kertas (misal F4 atau A4 Landscape)
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan-Tunggakan-Per-Desa.pdf');
    }
    
}
