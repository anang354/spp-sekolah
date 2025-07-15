<?php

namespace App\Filament\Resources\TagihanResource\Widgets;

use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TagihanBelumDibayarWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $getTagihan  = Tagihan::sum('jumlah_netto');
        $getPembayaran = Pembayaran::sum('jumlah_dibayar');
        $getData = $getTagihan - $getPembayaran;
        $formatView = "Rp " . number_format($getData, 0, ",", ".");
        $getDataSiswa  =  Siswa::whereHas('tagihans', function ($query) {
                            $query->where('status', '!=', 'lunas');
                        })->count();
        return [
            //
            Stat::make('Tagihan Belum Dibayar', $formatView)
            ->color('danger'),
            Stat::make('Siswa Belum Bayar', $getDataSiswa)
            ->color('danger'),
        ];
    }
}
