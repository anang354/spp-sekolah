<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PembayaranChartWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Grafik Pembayaran 5 Bulan Terakhir';

    protected function getData(): array
{
    // PERBAIKAN: Tambahkan ->startOfMonth() agar aman dijalankan di tanggal 31
    $today = Carbon::now()->startOfMonth(); 
    
    $months = collect();

    // Ambil daftar 5 bulan terakhir
    for ($i = 4; $i >= 0; $i--) {
        // Karena $today sudah tanggal 1, subMonths tidak akan error overflow lagi
        $months->push($today->copy()->subMonths($i)->format('Y-m'));
    }

    // Ambil data untuk jenis_keuangan = sekolah
    $sekolah = DB::table('pembayarans')
        ->join('tagihans', 'pembayarans.tagihan_id', '=', 'tagihans.id')
        ->selectRaw("DATE_FORMAT(pembayarans.created_at, '%Y-%m') as bulan, SUM(pembayarans.jumlah_dibayar) as total")
        ->where('tagihans.jenis_keuangan', 'sekolah')
        // Query database juga jadi lebih aman
        ->where('pembayarans.created_at', '>=', $today->copy()->subMonths(4)) 
        ->groupBy('bulan')
        ->pluck('total', 'bulan');

    // Ambil data untuk jenis_keuangan = pondok
    $pondok = DB::table('pembayarans')
        ->join('tagihans', 'pembayarans.tagihan_id', '=', 'tagihans.id')
        ->selectRaw("DATE_FORMAT(pembayarans.created_at, '%Y-%m') as bulan, SUM(pembayarans.jumlah_dibayar) as total")
        ->where('tagihans.jenis_keuangan', 'pondok')
        ->where('pembayarans.created_at', '>=', $today->copy()->subMonths(4))
        ->groupBy('bulan')
        ->pluck('total', 'bulan');

    // ... (Sisa kode ke bawah sama persis) ...
    
    // Susun data: isi 0 jika bulan kosong
    $dataSekolah = $months->mapWithKeys(fn($m) => [$m => $sekolah[$m] ?? 0]);
    $dataPondok = $months->mapWithKeys(fn($m) => [$m => $pondok[$m] ?? 0]);

    return [
        'labels' => $months->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('F Y'))->all(),
        'datasets' => [
            [
                'label' => 'Pembayaran Sekolah',
                'data' => $dataSekolah->values()->all(),
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                'fill' => true,
                'tension' => 0.4,
            ],
            [
                'label' => 'Pembayaran Pondok',
                'data' => $dataPondok->values()->all(),
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                'fill' => true,
                'tension' => 0.4,
            ],
        ],
    ];
}


    protected function getType(): string
    {
        return 'line';
    }
}
