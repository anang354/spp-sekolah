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
        $today = Carbon::today();
    $months = collect();

    // Ambil daftar 5 bulan terakhir
    for ($i = 4; $i >= 0; $i--) {
        $months->push($today->copy()->subMonths($i)->format('Y-m'));
    }

    // Ambil data dari database
    $results = DB::table('pembayarans')
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan, SUM(jumlah_dibayar) as total")
        ->where('created_at', '>=', $today->copy()->subMonths(5)->startOfMonth())
        ->groupBy('bulan')
        ->orderBy('bulan')
        ->pluck('total', 'bulan');
        // Susun data dengan bulan yang tidak ada diisi 0
    $data = $months->mapWithKeys(function ($month) use ($results) {
        return [$month => $results[$month] ?? 0];
    });
        return [
        'labels' => $data->keys()->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('F Y'))->all(),
        'datasets' => [
            [
                'label' => 'Total Pembayaran',
                'data' => $data->values()->all(),
                'fill' => true,
                'tension' => 0.4, // opsional: garis agak melengkung
            ],
        ],
    ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
