<?php

namespace App\Filament\Widgets;

use App\Models\Alumni;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TagihanAlumniWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Ambil data dari DB
        $alumnis = Alumni::query()
            ->withSum('pembayaranAlumni as total_dibayar', 'jumlah_dibayar')
            ->get();
        $totalTagihan = $alumnis->sum('jumlah_netto');
        $totalDibayar = $alumnis->sum('total_dibayar');
        $sisaTagihan = $totalTagihan - $totalDibayar;

        return [
            Stat::make('Total Tagihan', 'Rp ' . number_format($totalTagihan, 0, ',', '.'))
                ->description('Total jumlah tagihan semua alumni')
                ->color('gray'),

            Stat::make('Total Dibayar', 'Rp ' . number_format($totalDibayar, 0, ',', '.'))
                ->description('Total pembayaran yang masuk')
                ->color('success'),

            Stat::make('Sisa Tagihan', 'Rp ' . number_format($sisaTagihan, 0, ',', '.'))
                ->description('Tagihan yang belum dibayar')
                ->color('danger'),
        ];
    }
}
