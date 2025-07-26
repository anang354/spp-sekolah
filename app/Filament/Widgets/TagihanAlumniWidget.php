<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Alumni;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TagihanAlumniWidget extends BaseWidget
{
    //Disable Widget dari tampilan dashboard (Hanya tampil ketika dipanggil saja)
     protected static bool $isDiscovered = false;
    protected function getStats(): array
    {
        // Ambil data dari DB
        $alumnis = Alumni::query()
            ->withSum('pembayaranAlumni as total_dibayar', 'jumlah_dibayar')
            ->get();
            $totalTagihanPerKeuangan = $alumnis->groupBy('jenis_keuangan') // Kelompokkan koleksi berdasarkan kolom 'jenjang'
                                 ->map(function ($alumniGroup) {
                                     // Untuk setiap grup (misal grup SMP, grup SMA), jumlahkan 'jumlah_tagihan'
                                     return $alumniGroup->sum('jumlah_netto');
                                 });
            $totalPembayaranPerKeuangan = $alumnis->groupBy('jenis_keuangan') // Kelompokkan koleksi berdasarkan kolom 'jenjang'
                                 ->map(function ($alumniGroup) {
                                     // Untuk setiap grup (misal grup SMP, grup SMA), jumlahkan 'jumlah_tagihan'
                                     return $alumniGroup->sum('total_dibayar');
                                 });
            $totalTagihanSekolah = $totalTagihanPerKeuangan['sekolah'] ?? 0; // Menggunakan null coalescing operator jika jenjang tidak ada
            $totalTagihanPondok = $totalTagihanPerKeuangan['pondok'] ?? 0;
            $totalPembayaranSekolah = $totalPembayaranPerKeuangan['sekolah'] ?? 0;
            $totalPembayaranPondok = $totalPembayaranPerKeuangan['pondok'] ?? 0;

            $sisaTagihanSekolah = $totalTagihanSekolah - $totalPembayaranSekolah;
            $sisaTagihanPondok = $totalTagihanPondok - $totalPembayaranPondok;
        // $totalTagihan = $alumnis->sum('jumlah_netto');
        // $totalDibayar = $alumnis->sum('total_dibayar');
        // $sisaTagihan = $totalTagihan - $totalDibayar;
        
        $awalBulanIni = Carbon::now()->startOfMonth();
        // Dapatkan tanggal akhir bulan ini
        $akhirBulanIni = Carbon::now()->endOfMonth();
        $pembayaran = \App\Models\PembayaranALumni::whereBetween('created_at', [$awalBulanIni, $akhirBulanIni])->sum('jumlah_dibayar');

        return [
            Stat::make('Total Semua Tunggakan', 'Rp ' . number_format($sisaTagihanPondok + $sisaTagihanSekolah, 0, ',', '.'))
                ->description('Total jumlah tagihan semua alumni')
                ->color('info'),
            Stat::make("Pembayaran Bulan Ini {$awalBulanIni->translatedFormat('F Y')}", 'Rp ' . number_format($pembayaran, 0, ',', '.'))
                ->description('Total Pembayaran alumni bulan ini')
                ->color('warning'),
            Stat::make('Total Tunggakan Pondok', 'Rp ' . number_format($sisaTagihanPondok, 0, ',', '.'))
                ->description('Total Tunggakan Pondok')
                ->color('danger'),
            Stat::make('Total Tunggakan Sekolah', 'Rp ' . number_format($sisaTagihanSekolah, 0, ',', '.'))
                ->description('Total Tunggakan Sekolah')
                ->color('danger'),
        ];
    }
}
