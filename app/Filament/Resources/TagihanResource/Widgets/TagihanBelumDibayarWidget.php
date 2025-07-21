<?php

namespace App\Filament\Resources\TagihanResource\Widgets;

use Carbon\Carbon;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TagihanBelumDibayarWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;
        $tagihan  = Tagihan::query()
            ->where(function ($query) use ($bulan, $tahun) {
                $query->where('periode_tahun', '<', $tahun)
                      ->orWhere(function ($q) use ($bulan, $tahun) {
                          $q->where('periode_tahun', $tahun)
                            ->where('periode_bulan', '<=', $bulan);
                      });
            })
            ->with('pembayaran')
            ->get();
        $totalTagihan = $tagihan->sum('jumlah_netto');
        $totalDibayar = $tagihan->flatMap->pembayaran->sum('jumlah_dibayar');
        $sisa = $totalTagihan - $totalDibayar;

        // $getTagihan  = Tagihan::sum('jumlah_netto');
        // $getPembayaran = Pembayaran::sum('jumlah_dibayar');
        // $getData = $getTagihan - $getPembayaran;
        // $formatView = "Rp " . number_format($getData, 0, ",", ".");
        $getDataSiswa  =  Siswa::whereHas('tagihans', function ($query) {
                            $query->where('status', '!=', 'lunas');
                        })->count();
        return [
            //
            Stat::make("Total Tagihan s.d. {$now->translatedFormat('F Y')}", 'Rp ' . number_format($totalTagihan, 0, ',', '.'))
            ->description('Total jumlah tagihan semua siswa')
            ->color('info'),
            Stat::make("Total Dibayar s.d. {$now->translatedFormat('F Y')}", 'Rp ' . number_format($totalDibayar, 0, ',', '.'))
            ->description('Total pembayaran yang masuk')
                ->color('success'),
            Stat::make("Sisa Tagihan s.d. {$now->translatedFormat('F Y')}", 'Rp ' . number_format($sisa, 0, ',', '.'))
            ->description('Tagihan yang belum dibayar')
                ->color('danger'),
        ];
    }
}
